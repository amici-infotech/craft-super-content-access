<?php
/**
 * Super Content Access plugin for Craft CMS 5.x
 *
 * Element-level authorization with query-level access control.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess;

use amici\SuperContentAccess\base\PluginTrait;
use amici\SuperContentAccess\fields\AccessControlField;
use amici\SuperContentAccess\models\Settings;
use amici\SuperContentAccess\widgets\AccessBreakdown;
use amici\SuperContentAccess\widgets\AccessOverview;
use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin as CraftPlugin;
use craft\console\Application as ConsoleApplication;
use craft\elements\Entry;
use craft\events\DefineHtmlEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\Dashboard;
use craft\services\Fields;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * Super Content Access Plugin
 *
 * @author    Amici Infotech
 * @package   SuperContentAccess
 * @since     5.0.0
 *
 * @property Settings $settings
 * @method Settings getSettings()
 */
class Plugin extends CraftPlugin
{
    use PluginTrait;

    /**
     * @var Plugin|null Singleton plugin instance.
     */
    public static ?Plugin $plugin = null;

    /**
     * @var string Plugin handle used in routes and config.
     */
    public static string $pluginHandle = 'super-content-access';

    /**
     * @var string Database schema version.
     */
    public string $schemaVersion = '5.0.4';

    /**
     * @var bool Whether the plugin exposes CP settings.
     */
    public bool $hasCpSettings = true;

    /**
     * @var bool Whether the plugin exposes a CP section.
     */
    public bool $hasCpSection = true;

    /**
     * Initializes the plugin and registers components, routes, and query integration.
     *
     * @return void Nothing is returned.
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'amici\SuperContentAccess\console\controllers';
        }

        $this->_setPluginComponents();
        $this->_registerFieldType();
        $this->_registerElementSidebarWidget();
        $this->_registerDashboardWidgets();
        $this->_registerCpRoutes();
        $this->_registerPermissions();
        $this->getEntryQueryIntegrator()->register();

        Craft::info(
            Craft::t('super-content-access', '{name} plugin loaded', ['name' => $this->name]),
            __METHOD__
        );
    }

    /**
     * Creates the plugin settings model.
     *
     * @return Settings|null The settings model instance.
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * Redirects CP settings requests to the plugin settings screen.
     *
     * @return mixed The HTTP redirect response.
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('super-content-access/settings'));
    }

    /**
     * Builds the Control Panel nav item with subnav links.
     *
     * @return array|null Nav item configuration, or null when hidden.
     */
    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = $this->getSettings()->pluginName ?: 'Super Content Access';
        // Use the section root so the nav item (and its subnav) stays selected
        // across every plugin page, not just /settings.
        $item['url'] = 'super-content-access';

        $item['subnav']['channels'] = [
            'label' => Craft::t('super-content-access', 'General Access'),
            'url' => 'super-content-access/access/channels',
        ];

        $item['subnav']['settings'] = [
            'label' => Craft::t('super-content-access', 'Settings'),
            'url' => 'super-content-access/settings',
        ];

        return $item;
    }

    /**
     * Returns the path to the CP nav icon mask SVG.
     *
     * @return string|null Absolute path to the icon file.
     */
    protected function cpNavIconPath(): ?string
    {
        return $this->getBasePath() . DIRECTORY_SEPARATOR . 'icon-mask.svg';
    }

    /**
     * Registers the Access Control field type with Craft.
     *
     * @return void Nothing is returned.
     */
    private function _registerFieldType(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            static function (RegisterComponentTypesEvent $event): void {
                $event->types[] = AccessControlField::class;
            }
        );
    }

    /**
     * Registers dashboard widget types with Craft.
     *
     * @return void Nothing is returned.
     */
    private function _registerDashboardWidgets(): void
    {
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            static function (RegisterComponentTypesEvent $event): void {
                $event->types[] = AccessOverview::class;
                $event->types[] = AccessBreakdown::class;
            }
        );
    }

    /**
     * Appends the read-only access summary to entry editor sidebars.
     *
     * @return void Nothing is returned.
     */
    private function _registerElementSidebarWidget(): void
    {
        Event::on(
            Entry::class,
            Element::EVENT_DEFINE_SIDEBAR_HTML,
            function (DefineHtmlEvent $event): void {
                $element = $event->sender;

                if (!$element instanceof Entry) {
                    return;
                }

                $html = $this->getElementSidebarWidget()->render($element, $event->static);
                if ($html === '') {
                    return;
                }

                $event->html .= "\n" . $html;
            }
        );
    }

    /**
     * Registers Control Panel URL rules for the plugin section.
     *
     * @return void Nothing is returned.
     */
    private function _registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            static function (RegisterUrlRulesEvent $event): void {
                $event->rules['super-content-access'] = 'super-content-access/access/channels';
                $event->rules['super-content-access/settings'] = 'super-content-access/settings/index';
                $event->rules['super-content-access/access'] = 'super-content-access/access/index';
                $event->rules['super-content-access/access/channels'] = 'super-content-access/access/channels';
                $event->rules['super-content-access/access/channels/<section:{handle}>'] = 'super-content-access/access/channel';
            }
        );
    }

    /**
     * Registers user permissions for managing access policies.
     *
     * @return void Nothing is returned.
     */
    private function _registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            static function (RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                    'heading' => Craft::t('super-content-access', 'Super Content Access'),
                    'permissions' => [
                        'super-content-access:manage-policies' => [
                            'label' => Craft::t('super-content-access', 'Manage access policies'),
                        ],
                    ],
                ];
            }
        );
    }
}
