<?php
namespace amici\SuperContentAccess\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * CP assets for the Element Sidebar Widget.
 */
class ElementSidebarWidgetAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@amici/SuperContentAccess/resources/dist';
        $this->depends = [CpAsset::class];
        $this->css = ['css/element-sidebar-widget.css'];

        parent::init();
    }
}
