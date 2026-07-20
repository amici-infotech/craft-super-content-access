<?php
/**
 * Craft dashboard widget — Access Breakdown chart.
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
 * Doughnut breakdown of policies and audience principals.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AccessBreakdown extends Widget
{
    /**
     * Which series to chart: `scope` or `principals`.
     */
    public string $series = 'scope';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('super-content-access', 'Access Breakdown');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return 'chart-pie';
    }

    /**
     * @inheritdoc
     */
    protected static function allowMultipleInstances(): bool
    {
        return true;
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
        return match ($this->series) {
            'principals' => Craft::t('super-content-access', 'Audience Breakdown'),
            default => Craft::t('super-content-access', 'Policy Breakdown'),
        };
    }

    /**
     * @inheritdoc
     */
    public function getSubtitle(): ?string
    {
        return match ($this->series) {
            'principals' => Craft::t('super-content-access', 'Who is granted access'),
            default => Craft::t('super-content-access', 'Where policies are defined'),
        };
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['series'], 'in', 'range' => ['scope', 'principals']];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'super-content-access/_components/widgets/access-breakdown/settings',
            ['widget' => $this]
        );
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        $breakdown = Plugin::getInstance()->getDiagnostics()->breakdown();
        $series = $this->series === 'principals'
            ? $breakdown['byPrincipalType']
            : $breakdown['byScope'];

        $view = Craft::$app->getView();
        $view->registerAssetBundle(DashboardWidgetsAsset::class);

        return $view->renderTemplate('super-content-access/_components/widgets/access-breakdown/body', [
            'series' => $series,
            'total' => array_sum(array_column($series, 'value')),
        ]);
    }
}
