<?php
/**
 * Force-removes deprecated Access Control field records from Craft.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\migrations;

use Craft;
use craft\db\Migration;

/**
 * Force-removes deprecated Access Control field records.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class m260714_151000_delete_deprecated_access_control_field_records extends Migration
{
    /**
     * Deprecated Access Control field class name.
     */
    private const FIELD_TYPE = 'amici\\SuperContentAccess\\fields\\AccessControlField';

    /**
     * Deletes deprecated field records and related changed-field rows.
     *
     * @return bool True when the migration completes successfully.
     */
    public function safeUp(): bool
    {
        $fields = (new \craft\db\Query())
            ->select(['id', 'uid'])
            ->from('{{%fields}}')
            ->where(['type' => self::FIELD_TYPE])
            ->all();

        foreach ($fields as $field) {
            $fieldId = (int)$field['id'];
            $fieldUid = (string)$field['uid'];

            Craft::$app->getProjectConfig()->remove("fields.$fieldUid");
            $this->delete('{{%changedfields}}', ['fieldId' => $fieldId]);
            $this->delete('{{%fields}}', ['id' => $fieldId]);
        }

        return true;
    }

    /**
     * Deprecated field records cannot be restored automatically.
     *
     * @return bool True when the migration completes successfully.
     */
    public function safeDown(): bool
    {
        echo "Deprecated Access Control field records cannot be restored.\n";
        return true;
    }
}
