<?php
/**
 * CP assets for Super Content Access dashboard widgets.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Registers styles for Access Overview / Access Breakdown widgets.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.3
 */
class DashboardWidgetsAsset extends AssetBundle
{
    /**
     * Initializes source paths and dependencies.
     *
     * @return void Nothing is returned.
     */
    public function init(): void
    {
        $this->sourcePath = '@amici/SuperContentAccess/resources/dist';
        $this->depends = [CpAsset::class];
        $this->css = ['css/dashboard-widgets.css'];

        parent::init();
    }
}
