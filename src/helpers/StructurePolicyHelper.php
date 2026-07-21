<?php
/**
 * Structure ancestry helpers for inherited element access policies.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\helpers;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\migrations\Install;
use amici\SuperContentAccess\Plugin;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use yii\db\Expression;

/**
 * Resolves nearest-ancestor element policies for structured elements.
 *
 * Used by single-element authorization, query SQL, and the element sidebar.
 * Applies to structure entries, categories, and Commerce products that live in
 * a Craft structure. Elements outside a structure no-op safely.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class StructurePolicyHelper
{
    /**
     * Finds the nearest ancestor that has an element policy.
     *
     * Walks parent → parent → … → top. The first ancestor with an element
     * policy wins. Returns null when the element is not in a structure or no
     * ancestor has a policy.
     *
     * @param int $elementId Element ID (entry, category, or product).
     *
     * @return array{policy: AccessPolicy, ancestorId: int}|null
     */
    public static function nearestAncestorPolicy(int $elementId): ?array
    {
        $position = (new Query())
            ->select(['root', 'lft', 'rgt', 'structureId'])
            ->from([Table::STRUCTUREELEMENTS])
            ->where(['elementId' => $elementId])
            ->one();

        if ($position === null) {
            return null;
        }

        $ancestorIds = (new Query())
            ->select(['se.elementId'])
            ->from(['se' => Table::STRUCTUREELEMENTS])
            ->where([
                'and',
                ['se.root' => $position['root']],
                ['se.structureId' => $position['structureId']],
                ['<', 'se.lft', $position['lft']],
                ['>', 'se.rgt', $position['rgt']],
            ])
            ->orderBy(['se.lft' => SORT_DESC])
            ->column();

        if ($ancestorIds === []) {
            return null;
        }

        $policies = Plugin::getInstance()->getPolicies();

        foreach ($ancestorIds as $ancestorId) {
            $ancestorId = (int)$ancestorId;
            $policy = $policies->getForElementId($ancestorId);
            if ($policy instanceof AccessPolicy) {
                return [
                    'policy' => $policy,
                    'ancestorId' => $ancestorId,
                ];
            }
        }

        return null;
    }

    /**
     * Yii condition: the element has no element-scoped policy of its own.
     *
     * @return array Condition array for andWhere / where.
     */
    public static function noOwnElementPolicyCondition(): array
    {
        return [
            'not exists',
            (new Query())
                ->select(new Expression('1'))
                ->from(['ep' => Install::TABLE_POLICIES])
                ->where('[[ep.elementId]] = [[elements.id]]'),
        ];
    }

    /**
     * Yii condition: no ancestor in the structure tree has an element policy.
     *
     * Elements outside a structure never match the EXISTS, so this is true.
     *
     * @return array Condition array for andWhere / where.
     */
    public static function noAncestorElementPolicyCondition(): array
    {
        return [
            'not exists',
            (new Query())
                ->select(new Expression('1'))
                ->from(['se' => Table::STRUCTUREELEMENTS])
                ->innerJoin(
                    ['anc' => Table::STRUCTUREELEMENTS],
                    [
                        'and',
                        '[[anc.root]] = [[se.root]]',
                        '[[anc.structureId]] = [[se.structureId]]',
                        '[[anc.lft]] < [[se.lft]]',
                        '[[anc.rgt]] > [[se.rgt]]',
                    ]
                )
                ->innerJoin(
                    ['ap' => Install::TABLE_POLICIES],
                    '[[ap.elementId]] = [[anc.elementId]]'
                )
                ->where('[[se.elementId]] = [[elements.id]]'),
        ];
    }

    /**
     * Yii condition: policy `p` is the nearest ancestor element policy.
     *
     * Requires alias `p` on the policies table in the outer query.
     *
     * @return array Condition array for andWhere / where.
     */
    public static function nearestAncestorPolicyAppliesCondition(): array
    {
        return [
            'and',
            ['not', ['p.elementId' => null]],
            self::noOwnElementPolicyCondition(),
            [
                'exists',
                (new Query())
                    ->select(new Expression('1'))
                    ->from(['se' => Table::STRUCTUREELEMENTS])
                    ->innerJoin(
                        ['anc' => Table::STRUCTUREELEMENTS],
                        [
                            'and',
                            '[[anc.root]] = [[se.root]]',
                            '[[anc.structureId]] = [[se.structureId]]',
                            '[[anc.elementId]] = [[p.elementId]]',
                            '[[anc.lft]] < [[se.lft]]',
                            '[[anc.rgt]] > [[se.rgt]]',
                        ]
                    )
                    ->where('[[se.elementId]] = [[elements.id]]')
                    ->andWhere([
                        'not exists',
                        (new Query())
                            ->select(new Expression('1'))
                            ->from(['closer' => Table::STRUCTUREELEMENTS])
                            ->innerJoin(
                                ['cp' => Install::TABLE_POLICIES],
                                '[[cp.elementId]] = [[closer.elementId]]'
                            )
                            ->where([
                                'and',
                                '[[closer.root]] = [[se.root]]',
                                '[[closer.structureId]] = [[se.structureId]]',
                                '[[closer.lft]] < [[se.lft]]',
                                '[[closer.rgt]] > [[se.rgt]]',
                                '[[closer.lft]] > [[anc.lft]]',
                            ]),
                    ]),
            ],
        ];
    }

    /**
     * Loads an ancestor element for sidebar display, if available.
     *
     * @param int $ancestorId Ancestor element ID.
     *
     * @return ElementInterface|null Element model, or null when missing.
     */
    public static function ancestorElement(int $ancestorId): ?ElementInterface
    {
        $type = (new Query())
            ->select(['type'])
            ->from([Table::ELEMENTS])
            ->where(['id' => $ancestorId])
            ->scalar();

        if (!is_string($type) || $type === '' || !class_exists($type)) {
            return null;
        }

        /** @var class-string<ElementInterface> $type */
        /** @var ElementInterface|null $element */
        $element = $type::find()
            ->id($ancestorId)
            ->status(null)
            ->site('*')
            ->one();

        return $element;
    }
}
