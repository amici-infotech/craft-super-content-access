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
     * @var bool Whether Craft admins always see all protected content on the front end.
     */
    public bool $adminAlwaysAccess = true;

    /**
     * @var bool Whether entry authors always see their own entries on the front end.
     *
     * Applies to entries only (not categories or Commerce products).
     */
    public bool $authorAlwaysAccess = true;

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
            [['authorizationEnabled', 'adminAlwaysAccess', 'authorAlwaysAccess'], 'boolean'],
        ];
    }
}
