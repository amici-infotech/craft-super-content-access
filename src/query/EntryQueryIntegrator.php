<?php
/**
 * Applies query-level authorization constraints to EntryQuery before SQL runs.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\query;

use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\migrations\Install;
use amici\SuperContentAccess\Plugin;
use craft\base\Component;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use yii\base\Event;
use yii\db\Expression;

/**
 * Applies query-level authorization constraints to EntryQuery before SQL runs.
 *
 * Performance strategy:
 * - Skip entirely when no policies exist (native Craft speed).
 * - Skip when the queried section(s) cannot be affected by any policy.
 * - Otherwise apply a single anti-join NOT EXISTS (deny-form) instead of
 *   multiple correlated EXISTS / NOT EXISTS branches.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class EntryQueryIntegrator extends Component
{
    /**
     * @var bool Whether the beforePrepare listener has been registered.
     */
    private bool $registered = false;

    /**
     * @var bool Whether query integration is currently enabled.
     */
    private bool $enabled = true;

    /**
     * Request-scoped memo: null = unknown, true/false = known.
     *
     * @var bool|null
     */
    private ?bool $hasAnyPolicies = null;

    /**
     * Request-scoped memo of section IDs that have a channel default policy.
     *
     * @var int[]|null
     */
    private ?array $restrictedSectionIds = null;

    /**
     * Request-scoped memo: whether any element-scoped policies exist.
     *
     * @var bool|null
     */
    private ?bool $hasElementPolicies = null;

    /**
     * Registers the global beforePrepare listener.
     *
     * @return void Nothing is returned.
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        Event::on(
            ElementQuery::class,
            ElementQuery::EVENT_BEFORE_PREPARE,
            [$this, 'handleBeforePrepare']
        );

        $this->registered = true;
    }

    /**
     * Enables query-level authorization integration.
     *
     * @return void Nothing is returned.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disables query-level authorization integration.
     *
     * @return void Nothing is returned.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Whether query-level authorization integration is enabled.
     *
     * @return bool True when integration is active.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Clears request-scoped policy presence memos (used by probes / tests).
     *
     * @return void Nothing is returned.
     */
    public function resetMemo(): void
    {
        $this->hasAnyPolicies = null;
        $this->restrictedSectionIds = null;
        $this->hasElementPolicies = null;
    }

    /**
     * Applies access constraints before an entry query is prepared.
     *
     * @param Event $event The beforePrepare event.
     *
     * @return void Nothing is returned.
     */
    public function handleBeforePrepare(Event $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $query = $event->sender;

        if (!$query instanceof EntryQuery) {
            return;
        }

        $plugin = Plugin::getInstance();
        if (!$plugin->getSettings()->authorizationEnabled) {
            return;
        }

        $context = $plugin->getContextFactory()->create();

        // Locked decision: CP requests bypass authorization.
        if ($context->isCpRequest) {
            return;
        }

        // PERF: unprotected installs pay zero SQL overhead.
        if (!$this->hasAnyPolicies()) {
            return;
        }

        // PERF: if this query is limited to sections that have no channel
        // default and the install has no element policies, skip.
        if ($this->canSkipForQuery($query)) {
            return;
        }

        $this->applyAccessConstraint($query, $context);
    }

    /**
     * Applies the production access SQL for a given context (also used by probe).
     *
     * An entry is denied when the effective policy (entry, else channel) exists
     * but has no principal matching the current context. Otherwise it is shown.
     *
     * Implemented as one correlated NOT EXISTS anti-join so MySQL/Postgres can
     * short-circuit instead of evaluating several independent EXISTS branches.
     *
     * @param EntryQuery $query Entry query to constrain.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return void Nothing is returned.
     */
    public function applyAccessConstraint(EntryQuery $query, AuthorizationContext $context): void
    {
        $matchOn = $this->buildPrincipalMatchConditions($context, 'pp');

        // Policy applies to this entry when it is the entry policy, or the
        // channel default while no entry policy exists.
        $appliesToEntry = [
            'or',
            '[[p.elementId]] = [[elements.id]]',
            [
                'and',
                '[[p.sectionId]] = [[entries.sectionId]]',
                [
                    'not exists',
                    (new Query())
                        ->select(new Expression('1'))
                        ->from(['ep' => Install::TABLE_POLICIES])
                        ->where('[[ep.elementId]] = [[elements.id]]'),
                ],
            ],
        ];

        // Deny when an applicable policy has zero matching principals.
        $blockingPolicy = (new Query())
            ->select(new Expression('1'))
            ->from(['p' => Install::TABLE_POLICIES])
            ->leftJoin(
                ['pp' => Install::TABLE_PRINCIPALS],
                ['and', '[[pp.policyId]] = [[p.id]]', $matchOn]
            )
            ->where($appliesToEntry)
            ->andWhere(['pp.id' => null]);

        $query->andWhere(['not exists', $blockingPolicy]);
    }

    /**
     * Whether any access policies exist (request-memoized).
     *
     * @return bool True when at least one policy exists.
     */
    private function hasAnyPolicies(): bool
    {
        $this->hydratePresenceFlags();

        return (bool)$this->hasAnyPolicies;
    }

    /**
     * Whether any element-scoped policies exist (request-memoized).
     *
     * @return bool True when at least one element policy exists.
     */
    private function hasElementPolicies(): bool
    {
        $this->hydratePresenceFlags();

        return (bool)$this->hasElementPolicies;
    }

    /**
     * Loads both presence flags in a single EXISTS round-trip.
     *
     * @return void Nothing is returned.
     */
    private function hydratePresenceFlags(): void
    {
        if ($this->hasAnyPolicies !== null && $this->hasElementPolicies !== null) {
            return;
        }

        $flags = Plugin::getInstance()->getPolicyRepository()->presenceFlags();
        $this->hasAnyPolicies = $flags['any'];
        $this->hasElementPolicies = $flags['element'];
    }

    /**
     * Returns section IDs that currently have a channel default policy.
     *
     * @return int[] Restricted section IDs.
     */
    private function restrictedSectionIds(): array
    {
        if ($this->restrictedSectionIds === null) {
            $this->restrictedSectionIds = (new Query())
                ->select(['sectionId'])
                ->from([Install::TABLE_POLICIES])
                ->where(['not', ['sectionId' => null]])
                ->column();
            $this->restrictedSectionIds = array_map('intval', $this->restrictedSectionIds);
        }

        return $this->restrictedSectionIds;
    }

    /**
     * Whether this EntryQuery cannot hit any protected content.
     *
     * @param EntryQuery $query Entry query being prepared.
     *
     * @return bool True when authorization can be skipped.
     */
    private function canSkipForQuery(EntryQuery $query): bool
    {
        // Element policies can attach to any entry — cannot section-skip.
        if ($this->hasElementPolicies()) {
            return false;
        }

        $sectionIds = $this->normalizeSectionIds($query->sectionId);
        if ($sectionIds === null) {
            // Query spans all sections; only skip if no channel defaults either.
            return $this->restrictedSectionIds() === [];
        }

        if ($sectionIds === []) {
            // Empty section filter → query returns nothing anyway.
            return true;
        }

        $restricted = $this->restrictedSectionIds();
        if ($restricted === []) {
            return true;
        }

        foreach ($sectionIds as $sectionId) {
            if (in_array($sectionId, $restricted, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalizes an EntryQuery section filter into integer IDs.
     *
     * @param mixed $sectionId EntryQuery::$sectionId value.
     *
     * @return int[]|null Normalized IDs, or null when the query is not section-limited.
     */
    private function normalizeSectionIds(mixed $sectionId): ?array
    {
        if ($sectionId === null || $sectionId === '' || $sectionId === '*') {
            return null;
        }

        if (!is_array($sectionId)) {
            $sectionId = [$sectionId];
        }

        $ids = [];
        foreach ($sectionId as $id) {
            if (is_numeric($id)) {
                $ids[] = (int)$id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Builds OR conditions for principals that match the current context.
     *
     * @param AuthorizationContext $context Current request context.
     * @param string $alias Principals table alias to match against.
     *
     * @return array Yii condition array.
     */
    private function buildPrincipalMatchConditions(AuthorizationContext $context, string $alias): array
    {
        $conditions = ['or'];
        $registry = Plugin::getInstance()->getResolverRegistry();

        foreach ($registry->all() as $resolver) {
            $identifiers = $resolver->matchingIdentifiers($context);
            if ($identifiers === []) {
                continue;
            }

            $type = $resolver->getType();

            if (in_array($type, [PrincipalType::PUBLIC, PrincipalType::GUEST], true)) {
                $conditions[] = ["$alias.type" => $type];
                continue;
            }

            $conditions[] = [
                "$alias.type" => $type,
                "$alias.identifier" => count($identifiers) === 1 ? $identifiers[0] : $identifiers,
            ];
        }

        // Fail closed: with no matchable principals, deny protected entries.
        if ($conditions === ['or']) {
            $conditions[] = new Expression('0=1');
        }

        return $conditions;
    }
}
