<?php
/**
 * Active Record for access policy database rows.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\records;

use amici\SuperContentAccess\migrations\Install;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Access Policy Record
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 *
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
    /**
     * Returns the database table name used by this Active Record.
     *
     * @return string The table name.
     */
    public static function tableName(): string
    {
        return Install::TABLE_POLICIES;
    }

    /**
     * Returns the related policy principal records.
     *
     * @return ActiveQueryInterface The principals relation query.
     */
    public function getPrincipals(): ActiveQueryInterface
    {
        return $this->hasMany(PolicyPrincipalRecord::class, ['policyId' => 'id']);
    }
}
