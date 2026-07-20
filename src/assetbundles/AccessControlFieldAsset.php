<?php
/**
 * CP assets for the Access Control field input.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Registers the CSS and JS used by the Access Control field editor.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AccessControlFieldAsset extends AssetBundle
{
    /**
     * Initializes the asset bundle source paths and dependencies.
     *
     * @return void Nothing is returned.
     */
    public function init(): void
    {
        $this->sourcePath = '@amici/SuperContentAccess/resources/dist';
        $this->depends = [CpAsset::class];
        $this->css = ['css/access-control-field.css'];
        $this->js = ['js/access-control-field.js'];

        parent::init();
    }
}
