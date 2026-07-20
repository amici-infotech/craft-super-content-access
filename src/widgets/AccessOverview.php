<?php
/**
 * Craft dashboard widget — Access Overview stats.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\widgets;

use amici\SuperContentAccess\assetbundles\DashboardWidgetsAsset;
use amici\SuperContentAccess\Plugin;
use Craft;
use craft\base\Widget;

/**
 * Shows headline access-control stats on the CP dashboard.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AccessOverview extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('super-content-access', 'Access Overview');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return 'shield-halved';
    }

    /**
     * @inheritdoc
     */
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function maxColspan(): ?int
    {
        return 2;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        return Craft::t('super-content-access', 'Access Overview');
    }

    /**
     * @inheritdoc
     */
    public function getSubtitle(): ?string
    {
        $overview = Plugin::getInstance()->getDiagnostics()->overview();

        return $overview['authorizationEnabled']
            ? Craft::t('super-content-access', 'Authorization is enabled')
            : Craft::t('super-content-access', 'Authorization is disabled');
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(DashboardWidgetsAsset::class);

        return $view->renderTemplate('super-content-access/_components/widgets/access-overview/body', [
            'overview' => Plugin::getInstance()->getDiagnostics()->overview(),
        ]);
    }
}
