<?php
namespace amici\SuperContentAccess\migrations;

use Craft;
use craft\db\Migration;

/**
 * Force-removes deprecated Access Control field records.
 */
class m260714_151000_delete_deprecated_access_control_field_records extends Migration
{
    private const FIELD_TYPE = 'amici\\SuperContentAccess\\fields\\AccessControlField';

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

    public function safeDown(): bool
    {
        echo "Deprecated Access Control field records cannot be restored.\n";
        return true;
    }
}
