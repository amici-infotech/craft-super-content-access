<?php
namespace amici\SuperContentAccess\records;

use amici\SuperContentAccess\migrations\Install;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property int $policyId
 * @property string $type
 * @property string $identifier
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 *
 * @property-read AccessPolicyRecord|null $policy
 */
class PolicyPrincipalRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Install::TABLE_PRINCIPALS;
    }

    public function getPolicy(): ActiveQueryInterface
    {
        return $this->hasOne(AccessPolicyRecord::class, ['id' => 'policyId']);
    }
}
