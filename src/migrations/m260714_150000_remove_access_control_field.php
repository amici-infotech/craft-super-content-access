<?php
namespace amici\SuperContentAccess\migrations;

use Craft;
use craft\db\Migration;

/**
 * Removes the deprecated field-based Access Control implementation.
 */
class m260714_150000_remove_access_control_field extends Migration
{
    private const FIELD_TYPE = 'amici\\SuperContentAccess\\fields\\AccessControlField';

    public function safeUp(): bool
    {
        $fields = (new \craft\db\Query())
            ->select(['id', 'uid', 'handle'])
            ->from('{{%fields}}')
            ->where(['type' => self::FIELD_TYPE])
            ->all();

        foreach ($fields as $field) {
            $fieldId = (int)$field['id'];
            $fieldUid = (string)$field['uid'];

            try {
                Craft::$app->getFields()->deleteFieldById($fieldId);
            } catch (\Throwable) {
                // If Craft can't instantiate the removed field class, remove the config and DB row.
                Craft::$app->getProjectConfig()->remove("fields.$fieldUid");
                $this->delete('{{%fields}}', ['id' => $fieldId]);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "The deprecated Access Control field cannot be restored.\n";
        return true;
    }
}
