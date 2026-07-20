<?php
namespace amici\SuperContentAccess\models;

use craft\base\Model;

/**
 * Settings model for Super Content Access plugin options.
 */
class Settings extends Model
{
    public string $pluginName = 'Super Content Access';
    public bool $authorizationEnabled = true;

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            [['pluginName'], 'required'],
            [['pluginName'], 'string', 'max' => 255],
            [['authorizationEnabled'], 'boolean'],
        ];
    }
}
