<?php
/**
 * Applies query-level authorization constraints to Entry, Category, and Product queries.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\query;

use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\events\ModifyElementQueryEvent;
use amici\SuperContentAccess\helpers\CommerceHelper;
use amici\SuperContentAccess\migrations\Install;
use amici\SuperContentAccess\Plugin;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use yii\base\Event;
use yii\db\Expression;

/**
 * Applies query-level authorization constraints before SQL runs.
 *
 * Performance strategy:
 * - Skip entirely when no policies can affect the element type.
 * - Skip when the queried scope(s) cannot be affected by any default policy
 *   and no element policies exist for that type.
 * - Otherwise apply a single anti-join NOT EXISTS (deny-form).
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class ElementQueryIntegrator extends Component
{
    /**
     * Event fired immediately before authorization SQL is applied to a query.
     *
     * Handlers receive a {@see ModifyElementQueryEvent}. Set `$event->isValid = false`
     * to skip injecting constraints for that query only.
     */
    public const EVENT_BEFORE_MODIFY_QUERY = 'beforeModifyQuery';

    /**
     * Event fired immediately after authorization SQL has been applied to a query.
     *
     * Handlers receive a {@see ModifyElementQueryEvent}.
     */
    public const EVENT_AFTER_MODIFY_QUERY = 'afterModifyQuery';

    /**
     * @var bool Whether the beforePrepare listener has been registered.
     */
    private bool $registered = false;

    /**
     * @var bool Whether query integration is currently enabled.
     */
    private bool $enabled = true;

    /**
     * Request-scoped presence memos keyed by cache key.
     *
     * @var array<string, bool|int[]|null>
     */
    private array $memo = [];

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
        $this->memo = [];
    }

    /**
     * Applies access constraints before a supported element query is prepared.
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
        $config = $this->configForQuery($query);
        if ($config === null) {
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

        // Optional: Craft admins see everything on the front end.
        if ($plugin->getSettings()->adminAlwaysAccess && $context->isAdmin) {
            return;
        }

        if (!$this->hasPoliciesForType($config['elementType'], $config['scopeColumn'])) {
            return;
        }

        if ($this->canSkipForQuery($query, $config)) {
            return;
        }

        $this->applyAccessConstraint($query, $context, $config);
    }

    /**
     * Applies production access SQL for Entry queries (probe / PHP API helper).
     *
     * @param EntryQuery $query Entry query to constrain.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return void Nothing is returned.
     */
    public function applyAccessConstraintForEntry(EntryQuery $query, AuthorizationContext $context): void
    {
        $this->applyAccessConstraint($query, $context, $this->entryConfig());
    }

    /**
     * Applies production access SQL for Category queries.
     *
     * @param CategoryQuery $query Category query to constrain.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return void Nothing is returned.
     */
    public function applyAccessConstraintForCategory(CategoryQuery $query, AuthorizationContext $context): void
    {
        $this->applyAccessConstraint($query, $context, $this->categoryConfig());
    }

    /**
     * Applies production access SQL for Product queries.
     *
     * @param ElementQuery $query Product query to constrain.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return void Nothing is returned.
     */
    public function applyAccessConstraintForProduct(ElementQuery $query, AuthorizationContext $context): void
    {
        $config = $this->productConfig();
        if ($config === null) {
            return;
        }

        $this->applyAccessConstraint($query, $context, $config);
    }

    /**
     * Applies the production access SQL for a given context (also used by probe).
     *
     * When `$config` is omitted, Entry queries are assumed (backward compatible).
     *
     * @param ElementQuery $query Element query to constrain.
     * @param AuthorizationContext $context Current authorization context.
     * @param array{elementType: class-string, scopeColumn: string, scopeTableColumn: string, queryScopeProperty: string}|null $config
     *
     * @return void Nothing is returned.
     */
    public function applyAccessConstraint(ElementQuery $query, AuthorizationContext $context, ?array $config = null): void
    {
        if ($config === null) {
            if (!$query instanceof EntryQuery) {
                throw new \InvalidArgumentException('Config required for non-entry queries.');
            }
            $config = $this->entryConfig();
        }

        $beforeEvent = new ModifyElementQueryEvent([
            'sender' => $this,
            'query' => $query,
            'context' => $context,
            'elementType' => $config['elementType'],
            'scopeColumn' => $config['scopeColumn'],
        ]);
        $this->trigger(self::EVENT_BEFORE_MODIFY_QUERY, $beforeEvent);

        if (!$beforeEvent->isValid) {
            return;
        }

        $matchOn = $this->buildPrincipalMatchConditions($context, 'pp');
        $scopeColumn = $config['scopeColumn'];
        $scopeTableColumn = $config['scopeTableColumn'];

        // Policy applies when it is the element policy, or the scope default
        // while no element policy exists.
        $applies = [
            'or',
            '[[p.elementId]] = [[elements.id]]',
            [
                'and',
                "[[p.{$scopeColumn}]] = [[{$scopeTableColumn}]]",
                [
                    'not exists',
                    (new Query())
                        ->select(new Expression('1'))
                        ->from(['ep' => Install::TABLE_POLICIES])
                        ->where('[[ep.elementId]] = [[elements.id]]'),
                ],
            ],
        ];

        $blockingPolicy = (new Query())
            ->select(new Expression('1'))
            ->from(['p' => Install::TABLE_POLICIES])
            ->leftJoin(
                ['pp' => Install::TABLE_PRINCIPALS],
                ['and', '[[pp.policyId]] = [[p.id]]', $matchOn]
            )
            ->where($applies)
            ->andWhere(['pp.id' => null]);

        $allowed = ['not exists', $blockingPolicy];

        // Entries only: authors may always see their own entries when enabled.
        if (
            $config['elementType'] === Entry::class
            && Plugin::getInstance()->getSettings()->authorAlwaysAccess
            && $context->userId !== null
        ) {
            $allowed = [
                'or',
                $allowed,
                [
                    'exists',
                    (new Query())
                        ->select(new Expression('1'))
                        ->from(['ea' => Table::ENTRIES_AUTHORS])
                        ->where('[[ea.entryId]] = [[entries.id]]')
                        ->andWhere(['ea.authorId' => $context->userId]),
                ],
            ];
        }

        $query->andWhere($allowed);

        $this->trigger(self::EVENT_AFTER_MODIFY_QUERY, new ModifyElementQueryEvent([
            'sender' => $this,
            'query' => $query,
            'context' => $context,
            'elementType' => $config['elementType'],
            'scopeColumn' => $config['scopeColumn'],
        ]));
    }

    /**
     * @param mixed $query Candidate element query.
     *
     * @return array{elementType: class-string, scopeColumn: string, scopeTableColumn: string, queryScopeProperty: string}|null
     */
    private function configForQuery(mixed $query): ?array
    {
        if ($query instanceof EntryQuery) {
            return $this->entryConfig();
        }

        if ($query instanceof CategoryQuery) {
            return $this->categoryConfig();
        }

        $productQueryClass = 'craft\\commerce\\elements\\db\\ProductQuery';
        if (CommerceHelper::isAvailable() && class_exists($productQueryClass) && $query instanceof $productQueryClass) {
            return $this->productConfig();
        }

        return null;
    }

    /**
     * @return array{elementType: class-string, scopeColumn: string, scopeTableColumn: string, queryScopeProperty: string}
     */
    private function entryConfig(): array
    {
        return [
            'elementType' => Entry::class,
            'scopeColumn' => 'sectionId',
            'scopeTableColumn' => 'entries.sectionId',
            'queryScopeProperty' => 'sectionId',
        ];
    }

    /**
     * @return array{elementType: class-string, scopeColumn: string, scopeTableColumn: string, queryScopeProperty: string}
     */
    private function categoryConfig(): array
    {
        return [
            'elementType' => Category::class,
            'scopeColumn' => 'groupId',
            'scopeTableColumn' => 'categories.groupId',
            'queryScopeProperty' => 'groupId',
        ];
    }

    /**
     * @return array{elementType: class-string, scopeColumn: string, scopeTableColumn: string, queryScopeProperty: string}|null
     */
    private function productConfig(): ?array
    {
        if (!CommerceHelper::isAvailable()) {
            return null;
        }

        return [
            'elementType' => 'craft\\commerce\\elements\\Product',
            'scopeColumn' => 'productTypeId',
            'scopeTableColumn' => 'commerce_products.typeId',
            'queryScopeProperty' => 'typeId',
        ];
    }

    /**
     * Whether any policies can affect the given element type.
     *
     * @param class-string $elementType Element class.
     * @param string $scopeColumn Default-scope column on policies.
     *
     * @return bool True when authorization SQL may be needed.
     */
    private function hasPoliciesForType(string $elementType, string $scopeColumn): bool
    {
        $key = "hasPolicies:$elementType:$scopeColumn";
        if (array_key_exists($key, $this->memo) && is_bool($this->memo[$key])) {
            return $this->memo[$key];
        }

        $hasScope = (new Query())
            ->from([Install::TABLE_POLICIES])
            ->where(['not', [$scopeColumn => null]])
            ->exists();

        $hasElement = $this->hasElementPoliciesForType($elementType);
        $result = $hasScope || $hasElement;
        $this->memo[$key] = $result;

        return $result;
    }

    /**
     * Whether element-scoped policies exist for the given element type.
     *
     * @param class-string $elementType Element class.
     *
     * @return bool True when at least one matching element policy exists.
     */
    private function hasElementPoliciesForType(string $elementType): bool
    {
        $key = "elementPolicies:$elementType";
        if (array_key_exists($key, $this->memo) && is_bool($this->memo[$key])) {
            return $this->memo[$key];
        }

        $result = (new Query())
            ->from(['p' => Install::TABLE_POLICIES])
            ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[p.elementId]]')
            ->where(['e.type' => $elementType])
            ->exists();

        $this->memo[$key] = $result;

        return $result;
    }

    /**
     * Returns scope IDs that currently have a default policy for the column.
     *
     * @param string $scopeColumn Policy column (sectionId, groupId, productTypeId).
     *
     * @return int[] Restricted scope IDs.
     */
    private function restrictedScopeIds(string $scopeColumn): array
    {
        $key = "restricted:$scopeColumn";
        if (array_key_exists($key, $this->memo) && is_array($this->memo[$key])) {
            /** @var int[] $ids */
            $ids = $this->memo[$key];

            return $ids;
        }

        $ids = (new Query())
            ->select([$scopeColumn])
            ->from([Install::TABLE_POLICIES])
            ->where(['not', [$scopeColumn => null]])
            ->column();

        $ids = array_map('intval', $ids);
        $this->memo[$key] = $ids;

        return $ids;
    }

    /**
     * Whether this query cannot hit any protected content of its type.
     *
     * @param ElementQuery $query Element query being prepared.
     * @param array{elementType: class-string, scopeColumn: string, scopeTableColumn: string, queryScopeProperty: string} $config
     *
     * @return bool True when authorization can be skipped.
     */
    private function canSkipForQuery(ElementQuery $query, array $config): bool
    {
        if ($this->hasElementPoliciesForType($config['elementType'])) {
            return false;
        }

        $property = $config['queryScopeProperty'];
        $scopeIds = $this->normalizeScopeIds($query->$property ?? null);
        if ($scopeIds === null) {
            return $this->restrictedScopeIds($config['scopeColumn']) === [];
        }

        if ($scopeIds === []) {
            return true;
        }

        $restricted = $this->restrictedScopeIds($config['scopeColumn']);
        if ($restricted === []) {
            return true;
        }

        foreach ($scopeIds as $scopeId) {
            if (in_array($scopeId, $restricted, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalizes a query scope filter into integer IDs.
     *
     * @param mixed $scopeId Query scope property value.
     *
     * @return int[]|null Normalized IDs, or null when the query is not scope-limited.
     */
    private function normalizeScopeIds(mixed $scopeId): ?array
    {
        if ($scopeId === null || $scopeId === '' || $scopeId === '*') {
            return null;
        }

        if (!is_array($scopeId)) {
            $scopeId = [$scopeId];
        }

        $ids = [];
        foreach ($scopeId as $id) {
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

        // Fail closed: with no matchable principals, deny protected elements.
        if ($conditions === ['or']) {
            $conditions[] = new Expression('0=1');
        }

        return $conditions;
    }
}
