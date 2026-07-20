<?php
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
 */
class PolicyRepository extends Component
{
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

    public function existsForElementId(int $elementId): bool
    {
        return AccessPolicyRecord::find()
            ->where(['elementId' => $elementId])
            ->exists();
    }

    /**
     * @param PolicyPrincipal[] $principals
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
     * Loads the principals for a section-scoped (channel) policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function findBySectionId(int $sectionId): ?array
    {
        /** @var AccessPolicyRecord|null $record */
        $record = AccessPolicyRecord::find()
            ->where(['sectionId' => $sectionId])
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
     * Saves the section-scoped (channel) policy and its principals.
     *
     * @param int $sectionId Section ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForSection(int $sectionId, array $principals): void
    {
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            /** @var AccessPolicyRecord|null $record */
            $record = AccessPolicyRecord::find()
                ->where(['sectionId' => $sectionId])
                ->one();

            $now = Db::prepareDateForDb(new DateTime());

            if ($record === null) {
                $record = new AccessPolicyRecord([
                    'sectionId' => $sectionId,
                    'uid' => StringHelper::UUID(),
                    'dateCreated' => $now,
                ]);
            }

            $record->dateUpdated = $now;

            if (!$record->save(false)) {
                throw new \RuntimeException('Unable to save section access policy.');
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
                    throw new \RuntimeException('Unable to save section policy principal.');
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes the section-scoped (channel) policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteBySectionId(int $sectionId): bool
    {
        /** @var AccessPolicyRecord|null $record */
        $record = AccessPolicyRecord::find()
            ->where(['sectionId' => $sectionId])
            ->one();

        if ($record === null) {
            return false;
        }

        return (bool)$record->delete();
    }

    /**
     * Fast existence check used by diagnostics / probes.
     */
    public function countPolicies(): int
    {
        return (int)(new Query())
            ->from([AccessPolicyRecord::tableName()])
            ->count();
    }

    /**
     * Whether any policy row exists (stops at the first match).
     *
     * Prefer this over countPolicies() on the request hot path.
     *
     * @return bool True when at least one policy exists.
     */
    public function existsAny(): bool
    {
        return (new Query())
            ->from([AccessPolicyRecord::tableName()])
            ->exists();
    }

    /**
     * Whether any element-scoped policy exists (stops at the first match).
     *
     * @return bool True when at least one element policy exists.
     */
    public function existsElementPolicy(): bool
    {
        return (new Query())
            ->from([AccessPolicyRecord::tableName()])
            ->where(['not', ['elementId' => null]])
            ->exists();
    }

    /**
     * Cheap presence flags for the query fast-path.
     *
     * Uses EXISTS (LIMIT 1) so cost stays flat even when many policies exist.
     * Short-circuits the element check when no policies exist at all.
     *
     * @return array{any: bool, element: bool}
     */
    public function presenceFlags(): array
    {
        $any = $this->existsAny();

        return [
            'any' => $any,
            'element' => $any && $this->existsElementPolicy(),
        ];
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
     * Counts section-scoped (channel default) policies.
     *
     * @return int Section policy count.
     */
    public function countSectionPolicies(): int
    {
        return (int)(new Query())
            ->from([AccessPolicyRecord::tableName()])
            ->where(['not', ['sectionId' => null]])
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
