<?php
/**
 * Persistence mapping between AccessPolicy domain objects and database records.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\repositories;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\records\AccessPolicyRecord;
use amici\SuperContentAccess\records\PolicyPrincipalRecord;
use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use DateTime;
use yii\base\Component;

/**
 * Persistence mapping between AccessPolicy domain objects and DB records.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class PolicyRepository extends Component
{
    /**
     * Loads the access policy for an element ID.
     *
     * @param int $elementId Element ID to look up.
     *
     * @return AccessPolicy|null The policy, or null when none exists.
     */
    public function findByElementId(int $elementId): ?AccessPolicy
    {
        /** @var AccessPolicyRecord|null $record */
        $record = AccessPolicyRecord::find()
            ->where(['elementId' => $elementId])
            ->with('principals')
            ->one();

        if ($record === null) {
            return null;
        }

        return $this->toDomain($record);
    }

    /**
     * Persists principals for an element-scoped policy.
     *
     * @param int $elementId Element ID to protect.
     * @param PolicyPrincipal[] $principals Principals to save.
     *
     * @return AccessPolicy The saved policy.
     */
    public function save(int $elementId, array $principals): AccessPolicy
    {
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            /** @var AccessPolicyRecord|null $record */
            $record = AccessPolicyRecord::find()
                ->where(['elementId' => $elementId])
                ->one();

            $now = Db::prepareDateForDb(new DateTime());

            if ($record === null) {
                $record = new AccessPolicyRecord([
                    'elementId' => $elementId,
                    'uid' => StringHelper::UUID(),
                    'dateCreated' => $now,
                ]);
            }

            $record->dateUpdated = $now;

            if (!$record->save(false)) {
                throw new \RuntimeException('Unable to save access policy.');
            }

            PolicyPrincipalRecord::deleteAll(['policyId' => $record->id]);

            foreach ($principals as $principal) {
                $principalRecord = new PolicyPrincipalRecord([
                    'policyId' => $record->id,
                    'type' => $principal->type,
                    'identifier' => $principal->identifier,
                    'uid' => StringHelper::UUID(),
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                ]);

                if (!$principalRecord->save(false)) {
                    throw new \RuntimeException('Unable to save policy principal.');
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $saved = $this->findByElementId($elementId);
        if ($saved === null) {
            throw new \RuntimeException('Access policy missing after save.');
        }

        return $saved;
    }

    /**
     * Deletes the element-scoped policy for an element ID.
     *
     * @param int $elementId Element ID whose policy should be removed.
     *
     * @return bool True when a policy was deleted.
     */
    public function deleteByElementId(int $elementId): bool
    {
        /** @var AccessPolicyRecord|null $record */
        $record = AccessPolicyRecord::find()
            ->where(['elementId' => $elementId])
            ->one();

        if ($record === null) {
            return false;
        }

        return (bool)$record->delete();
    }

    /**
     * Loads the principals for a section-scoped (channel/structure default) policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function findBySectionId(int $sectionId): ?array
    {
        return $this->findByScopeColumn('sectionId', $sectionId);
    }

    /**
     * Saves the section-scoped (channel/structure) policy and its principals.
     *
     * @param int $sectionId Section ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForSection(int $sectionId, array $principals): void
    {
        $this->saveForScopeColumn('sectionId', $sectionId, $principals, 'section');
    }

    /**
     * Deletes the section-scoped (channel/structure) policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteBySectionId(int $sectionId): bool
    {
        return $this->deleteByScopeColumn('sectionId', $sectionId);
    }

    /**
     * Loads the principals for a category-group default policy.
     *
     * @param int $groupId Category group ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function findByGroupId(int $groupId): ?array
    {
        return $this->findByScopeColumn('groupId', $groupId);
    }

    /**
     * Saves a category-group default policy and its principals.
     *
     * @param int $groupId Category group ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForGroup(int $groupId, array $principals): void
    {
        $this->saveForScopeColumn('groupId', $groupId, $principals, 'group');
    }

    /**
     * Deletes a category-group default policy.
     *
     * @param int $groupId Category group ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteByGroupId(int $groupId): bool
    {
        return $this->deleteByScopeColumn('groupId', $groupId);
    }

    /**
     * Loads the principals for a Commerce product-type default policy.
     *
     * @param int $productTypeId Product type ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function findByProductTypeId(int $productTypeId): ?array
    {
        return $this->findByScopeColumn('productTypeId', $productTypeId);
    }

    /**
     * Saves a Commerce product-type default policy and its principals.
     *
     * @param int $productTypeId Product type ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForProductType(int $productTypeId, array $principals): void
    {
        $this->saveForScopeColumn('productTypeId', $productTypeId, $principals, 'product type');
    }

    /**
     * Deletes a Commerce product-type default policy.
     *
     * @param int $productTypeId Product type ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteByProductTypeId(int $productTypeId): bool
    {
        return $this->deleteByScopeColumn('productTypeId', $productTypeId);
    }

    /**
     * Loads principals for a default-scope policy column.
     *
     * @param string $column One of sectionId, groupId, productTypeId.
     * @param int $id Scope ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    private function findByScopeColumn(string $column, int $id): ?array
    {
        $this->assertScopeColumn($column);

        /** @var AccessPolicyRecord|null $record */
        $record = AccessPolicyRecord::find()
            ->where([$column => $id])
            ->with('principals')
            ->one();

        if ($record === null) {
            return null;
        }

        $principals = [];
        foreach ($record->principals as $principalRecord) {
            $principals[] = new PolicyPrincipal(
                $principalRecord->type,
                $principalRecord->identifier,
                (int)$principalRecord->id,
            );
        }

        return $principals;
    }

    /**
     * Saves a default-scope policy and its principals.
     *
     * @param string $column One of sectionId, groupId, productTypeId.
     * @param int $id Scope ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     * @param string $label Human label for error messages.
     *
     * @return void Nothing is returned.
     */
    private function saveForScopeColumn(string $column, int $id, array $principals, string $label): void
    {
        $this->assertScopeColumn($column);

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            /** @var AccessPolicyRecord|null $record */
            $record = AccessPolicyRecord::find()
                ->where([$column => $id])
                ->one();

            $now = Db::prepareDateForDb(new DateTime());

            if ($record === null) {
                $record = new AccessPolicyRecord([
                    $column => $id,
                    'uid' => StringHelper::UUID(),
                    'dateCreated' => $now,
                ]);
            }

            $record->dateUpdated = $now;

            if (!$record->save(false)) {
                throw new \RuntimeException("Unable to save {$label} access policy.");
            }

            PolicyPrincipalRecord::deleteAll(['policyId' => $record->id]);

            foreach ($principals as $principal) {
                $principalRecord = new PolicyPrincipalRecord([
                    'policyId' => $record->id,
                    'type' => $principal->type,
                    'identifier' => $principal->identifier,
                    'uid' => StringHelper::UUID(),
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                ]);

                if (!$principalRecord->save(false)) {
                    throw new \RuntimeException("Unable to save {$label} policy principal.");
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes a default-scope policy.
     *
     * @param string $column One of sectionId, groupId, productTypeId.
     * @param int $id Scope ID.
     *
     * @return bool Whether a policy was deleted.
     */
    private function deleteByScopeColumn(string $column, int $id): bool
    {
        $this->assertScopeColumn($column);

        /** @var AccessPolicyRecord|null $record */
        $record = AccessPolicyRecord::find()
            ->where([$column => $id])
            ->one();

        if ($record === null) {
            return false;
        }

        return (bool)$record->delete();
    }

    /**
     * @param string $column Candidate scope column.
     *
     * @return void Nothing is returned.
     */
    private function assertScopeColumn(string $column): void
    {
        if (!in_array($column, ['sectionId', 'groupId', 'productTypeId'], true)) {
            throw new \InvalidArgumentException("Unsupported policy scope column: {$column}");
        }
    }

    /**
     * Counts all access policy rows.
     *
     * @return int Total policy count.
     */
    public function countPolicies(): int
    {
        return (int)(new Query())
            ->from([AccessPolicyRecord::tableName()])
            ->count();
    }

    /**
     * Counts element-scoped policies.
     *
     * @return int Element policy count.
     */
    public function countElementPolicies(): int
    {
        return (int)(new Query())
            ->from([AccessPolicyRecord::tableName()])
            ->where(['not', ['elementId' => null]])
            ->count();
    }

    /**
     * Counts section-scoped (channel/structure default) policies.
     *
     * @return int Section policy count.
     */
    public function countSectionPolicies(): int
    {
        return $this->countScopePolicies('sectionId');
    }

    /**
     * Counts category-group default policies.
     *
     * @return int Group policy count.
     */
    public function countGroupPolicies(): int
    {
        return $this->countScopePolicies('groupId');
    }

    /**
     * Counts Commerce product-type default policies.
     *
     * @return int Product type policy count.
     */
    public function countProductTypePolicies(): int
    {
        return $this->countScopePolicies('productTypeId');
    }

    /**
     * Counts default-scope policies for a column.
     *
     * @param string $column One of sectionId, groupId, productTypeId.
     *
     * @return int Policy count.
     */
    private function countScopePolicies(string $column): int
    {
        $this->assertScopeColumn($column);

        return (int)(new Query())
            ->from([AccessPolicyRecord::tableName()])
            ->where(['not', [$column => null]])
            ->count();
    }

    /**
     * Counts principals, optionally grouped by type.
     *
     * @return array{total: int, byType: array<string, int>} Principal totals.
     */
    public function countPrincipals(): array
    {
        $rows = (new Query())
            ->select(['type', 'cnt' => 'COUNT(*)'])
            ->from([PolicyPrincipalRecord::tableName()])
            ->groupBy(['type'])
            ->all();

        $byType = [];
        $total = 0;

        foreach ($rows as $row) {
            $count = (int)$row['cnt'];
            $byType[(string)$row['type']] = $count;
            $total += $count;
        }

        return [
            'total' => $total,
            'byType' => $byType,
        ];
    }

    /**
     * Maps a database record to a domain AccessPolicy object.
     *
     * @param AccessPolicyRecord $record Policy record to convert.
     *
     * @return AccessPolicy The domain policy object.
     */
    private function toDomain(AccessPolicyRecord $record): AccessPolicy
    {
        $principals = [];

        foreach ($record->principals as $principalRecord) {
            $principals[] = new PolicyPrincipal(
                $principalRecord->type,
                $principalRecord->identifier,
                (int)$principalRecord->id,
            );
        }

        return new AccessPolicy(
            (int)$record->elementId,
            $principals,
            (int)$record->id,
            $record->uid,
        );
    }
}
