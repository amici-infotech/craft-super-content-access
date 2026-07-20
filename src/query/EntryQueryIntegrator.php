<?php
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
 */
class EntryQueryIntegrator extends Component
{
    private bool $registered = false;
    private bool $enabled = true;

    /**
     * Request-scoped memo: null = unknown, true/false = known.
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
     */
    private ?bool $hasElementPolicies = null;

    /**
     * Register the global beforePrepare listener.
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

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Clears request-scoped policy presence memos (used by probes / tests).
     */
    public function resetMemo(): void
    {
        $this->hasAnyPolicies = null;
        $this->restrictedSectionIds = null;
        $this->hasElementPolicies = null;
    }

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
     * Apply the production access SQL for a given context (also used by probe).
     *
     * An entry is denied when the effective policy (entry, else channel) exists
     * but has no principal matching the current context. Otherwise it is shown.
     *
     * Implemented as one correlated NOT EXISTS anti-join so MySQL/Postgres can
     * short-circuit instead of evaluating several independent EXISTS branches.
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
     */
    /**
     * Whether any access policies exist (request-memoized).
     */
    private function hasAnyPolicies(): bool
    {
        $this->hydratePresenceFlags();

        return (bool)$this->hasAnyPolicies;
    }

    /**
     * Whether any element-scoped policies exist (request-memoized).
     */
    private function hasElementPolicies(): bool
    {
        $this->hydratePresenceFlags();

        return (bool)$this->hasElementPolicies;
    }

    /**
     * Loads both presence flags in a single EXISTS round-trip.
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
     * Section IDs that currently have a channel default policy.
     *
     * @return int[]
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
     * True when this EntryQuery cannot hit any protected content.
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
     * Build OR conditions for principals that match the current context.
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
