<?php
/**
 * CP assets for the element editor sidebar access summary.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Registers styles for the Element Sidebar Widget.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class ElementSidebarWidgetAsset extends AssetBundle
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
        $this->css = ['css/element-sidebar-widget.css'];

        parent::init();
    }
}
