<?php
/**
 * Detect whether Craft Commerce is available for product authorization.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\helpers;

use Craft;

/**
 * Soft-dependency helpers for Craft Commerce.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class CommerceHelper
{
    /**
     * Whether Commerce is installed, enabled, and loadable.
     *
     * @return bool True when product authorization can run.
     */
    public static function isAvailable(): bool
    {
        if (
            !class_exists('craft\\commerce\\Plugin')
            || !class_exists('craft\\commerce\\elements\\Product')
            || !class_exists('craft\\commerce\\elements\\db\\ProductQuery')
        ) {
            return false;
        }

        $plugins = Craft::$app->getPlugins();

        return $plugins->isPluginInstalled('commerce') && $plugins->isPluginEnabled('commerce');
    }
}
