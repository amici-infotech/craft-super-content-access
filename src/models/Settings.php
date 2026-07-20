<?php
/**
 * Plugin settings model for Super Content Access.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\models;

use craft\base\Model;

/**
 * Settings model for Super Content Access plugin options.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class Settings extends Model
{
    /**
     * @var string Display name shown in the CP nav.
     */
    public string $pluginName = 'Super Content Access';

    /**
     * @var bool Whether query- and element-level authorization is active.
     */
    public bool $authorizationEnabled = true;

    /**
     * Returns validation rules for plugin settings.
     *
     * @return array Yii validation rules.
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
