<?php
namespace amici\SuperContentAccess\controllers;

use amici\SuperContentAccess\Plugin;
use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * Plugin settings controller.
 */
class SettingsController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireAdmin();
        return parent::beforeAction($action);
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate('super-content-access/settings/index', [
            'plugin' => $plugin,
            'settings' => $plugin->getSettings(),
        ]);
    }

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
