<?php
/**
 * Plugin settings controller for Super Content Access.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\controllers;

use amici\SuperContentAccess\Plugin;
use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * Plugin settings controller.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class SettingsController extends Controller
{
    /**
     * Restricts settings actions to Craft admins.
     *
     * @param mixed $action Action being run.
     *
     * @return bool Whether the action may run.
     */
    public function beforeAction($action): bool
    {
        $this->requireAdmin();
        return parent::beforeAction($action);
    }

    /**
     * Renders the plugin settings screen.
     *
     * @return Response Rendered settings page.
     */
    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate('super-content-access/settings/index', [
            'plugin' => $plugin,
            'settings' => $plugin->getSettings(),
        ]);
    }

    /**
     * Saves plugin settings from a POST request.
     *
     * @return Response|null Success response, or null on validation failure.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();
        $settings->pluginName = (string)$this->request->getBodyParam('pluginName', $settings->pluginName);
        $settings->authorizationEnabled = (bool)$this->request->getBodyParam('authorizationEnabled');

        if (!$settings->validate() || !Craft::$app->getPlugins()->savePluginSettings($plugin, $settings->toArray())) {
            return $this->asModelFailure(
                $settings,
                Craft::t('super-content-access', 'Couldn’t save settings.'),
                'settings'
            );
        }

        return $this->asSuccess(Craft::t('super-content-access', 'Settings saved.'));
    }
}
