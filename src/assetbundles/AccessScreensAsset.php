<?php
/**
 * CP assets for the General Access settings screens.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Registers styles for the channels list and channel editor screens.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AccessScreensAsset extends AssetBundle
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
        $this->css = ['css/access-screens.css'];

        parent::init();
    }
}
