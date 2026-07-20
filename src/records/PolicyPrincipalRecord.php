<?php
/**
 * Active Record for policy principal database rows.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\records;

use amici\SuperContentAccess\migrations\Install;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Policy Principal Record
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 *
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
    /**
     * Returns the database table name used by this Active Record.
     *
     * @return string The table name.
     */
    public static function tableName(): string
    {
        return Install::TABLE_PRINCIPALS;
    }

    /**
     * Returns the related access policy record.
     *
     * @return ActiveQueryInterface The policy relation query.
     */
    public function getPolicy(): ActiveQueryInterface
    {
        return $this->hasOne(AccessPolicyRecord::class, ['id' => 'policyId']);
    }
}
