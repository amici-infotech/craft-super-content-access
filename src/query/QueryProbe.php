<?php
/**
 * Diagnostic helper for seeding sample policies and probing EntryQuery constraints.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\query;

use amici\SuperContentAccess\migrations\Install;
use amici\SuperContentAccess\Plugin;
use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use DateTime;
use yii\base\Event;

/**
 * Console diagnostic helper for verifying Entry query authorization SQL.
 *
 * Uses the same production constraint SQL as the front end. Entry-only for now;
 * category and product filtering should be verified with normal front-end queries.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class QueryProbe extends Component
{
    /**
     * Builds an EntryQuery with optional section and limit filters.
     *
     * @param string|null $section Section handle filter, or null for all sections.
     * @param int|null $limit Maximum number of entries to return.
     *
     * @return EntryQuery The configured entry query.
     */
    public function createEntryQuery(?string $section = null, ?int $limit = 20): EntryQuery
    {
        $query = Entry::find()->status(null);

        if ($section !== null && $section !== '') {
            $query->section($section);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Attaches a temporary beforePrepare listener that injects access SQL.
     *
     * Returns a callable that detaches the listener.
     *
     * @param int|null $userId User ID for the synthetic context.
     * @param int[] $groupIds Group IDs for the synthetic context.
     * @param bool $isGuest Whether the synthetic context is a guest.
     *
     * @return callable(): void Callable that removes the listener.
     */
    public function attachConstraintListener(
        ?int $userId = null,
        array $groupIds = [],
        bool $isGuest = false
    ): callable {
        $handler = function (Event $event) use ($userId, $groupIds, $isGuest): void {
            $query = $event->sender;

            if (!$query instanceof EntryQuery) {
                return;
            }

            $this->applyAccessConstraint($query, $userId, $groupIds, $isGuest);
        };

        Event::on(ElementQuery::class, ElementQuery::EVENT_BEFORE_PREPARE, $handler);

        return static function () use ($handler): void {
            Event::off(ElementQuery::class, ElementQuery::EVENT_BEFORE_PREPARE, $handler);
        };
    }

    /**
     * Injects production access constraints using a synthetic context.
     *
     * @param EntryQuery $query Entry query to constrain.
     * @param int|null $userId User ID for the synthetic context.
     * @param int[] $groupIds Group IDs for the synthetic context.
     * @param bool $isGuest Whether the synthetic context is a guest.
     *
     * @return void Nothing is returned.
     */
    public function applyAccessConstraint(
        EntryQuery $query,
        ?int $userId = null,
        array $groupIds = [],
        bool $isGuest = false
    ): void {
        $context = Plugin::getInstance()->getContextFactory()->createFromParams(
            $userId,
            $groupIds,
            $isGuest,
        );

        Plugin::getInstance()->getElementQueryIntegrator()->applyAccessConstraint($query, $context);
    }

    /**
     * Seeds a sample element policy so constrained vs baseline results differ.
     *
     * Creates:
     * - policy on the first matching entry (or --entryId)
     * - principal type=user for $userId (so only that user matches)
     *
     * @param int|null $entryId Entry ID to protect, or null for the first entry.
     * @param int|null $userId User ID allowed by the seeded principal.
     *
     * @return array{entryId: int, policyId: int, principalId: int, created: bool} Seed result metadata.
     */
    public function seedSamplePolicy(?int $entryId = null, ?int $userId = 1): array
    {
        $userId = $userId ?? 1;

        if ($entryId === null) {
            $entryId = (new Query())
                ->select(['elements.id'])
                ->from(['elements' => Table::ELEMENTS])
                ->innerJoin(['entries' => Table::ENTRIES], '[[entries.id]] = [[elements.id]]')
                ->where(['elements.dateDeleted' => null])
                ->orderBy(['elements.id' => SORT_ASC])
                ->scalar();

            if (!$entryId) {
                throw new \RuntimeException('No entries found to seed a sample policy.');
            }

            $entryId = (int)$entryId;
        }

        $db = Craft::$app->getDb();
        $existingPolicyId = (new Query())
            ->select(['id'])
            ->from([Install::TABLE_POLICIES])
            ->where(['elementId' => $entryId])
            ->scalar();

        if ($existingPolicyId) {
            $policyId = (int)$existingPolicyId;
            $principalId = (new Query())
                ->select(['id'])
                ->from([Install::TABLE_PRINCIPALS])
                ->where([
                    'policyId' => $policyId,
                    'type' => 'user',
                    'identifier' => (string)$userId,
                ])
                ->scalar();

            if (!$principalId) {
                $principalId = $this->insertPrincipal($policyId, 'user', (string)$userId);
            }

            return [
                'entryId' => $entryId,
                'policyId' => $policyId,
                'principalId' => (int)$principalId,
                'created' => false,
            ];
        }

        $now = Db::prepareDateForDb(new DateTime());
        $db->createCommand()->insert(Install::TABLE_POLICIES, [
            'elementId' => $entryId,
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => StringHelper::UUID(),
        ])->execute();

        $policyId = (int)$db->getLastInsertID();
        $principalId = $this->insertPrincipal($policyId, 'user', (string)$userId);

        return [
            'entryId' => $entryId,
            'policyId' => $policyId,
            'principalId' => $principalId,
            'created' => true,
        ];
    }

    /**
     * Deletes every access policy and principal row.
     *
     * Destructive — intended only for local diagnostic resets. Callers must
     * require an explicit force flag before invoking this.
     *
     * @return int Number of policy rows removed.
     */
    public function wipeAllPolicies(): int
    {
        $db = Craft::$app->getDb();
        $count = (int)(new Query())->from([Install::TABLE_POLICIES])->count();
        $db->createCommand()->delete(Install::TABLE_PRINCIPALS)->execute();
        $db->createCommand()->delete(Install::TABLE_POLICIES)->execute();

        return $count;
    }

    /**
     * Inserts a principal row for a seeded policy.
     *
     * @param int $policyId Policy ID to attach the principal to.
     * @param string $type Principal type handle.
     * @param string $identifier Principal identifier.
     *
     * @return int The inserted principal ID.
     */
    private function insertPrincipal(int $policyId, string $type, string $identifier): int
    {
        $db = Craft::$app->getDb();
        $now = Db::prepareDateForDb(new DateTime());

        $db->createCommand()->insert(Install::TABLE_PRINCIPALS, [
            'policyId' => $policyId,
            'type' => $type,
            'identifier' => $identifier,
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => StringHelper::UUID(),
        ])->execute();

        return (int)$db->getLastInsertID();
    }
}
