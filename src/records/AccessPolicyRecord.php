<?php
namespace amici\SuperContentAccess\records;

use amici\SuperContentAccess\migrations\Install;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property int|null $elementId
 * @property int|null $sectionId
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 *
 * @property-read PolicyPrincipalRecord[] $principals
 */
class AccessPolicyRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Install::TABLE_POLICIES;
    }

    public function getPrincipals(): ActiveQueryInterface
    {
        return $this->hasMany(PolicyPrincipalRecord::class, ['policyId' => 'id']);
    }
}
