<?php
/**
 * General Access management for channels, category groups, and product types.
 *
 * Provides the settings area where administrators pick a default access policy
 * for a whole scope. These defaults apply to every element in that scope that
 * has no element-level policy of its own.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\controllers;

use amici\SuperContentAccess\assetbundles\AccessControlFieldAsset;
use amici\SuperContentAccess\assetbundles\AccessScreensAsset;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\fields\data\AccessControlValue;
use amici\SuperContentAccess\helpers\CommerceHelper;
use amici\SuperContentAccess\Plugin;
use Craft;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\models\Section;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Controls the General Access settings screens.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AccessController extends Controller
{
    /**
     * Restricts the whole controller to CP requests with manage permission.
     *
     * @param mixed $action Action being run.
     *
     * @return bool Whether the action may run.
     */
    public function beforeAction($action): bool
    {
        $this->requireCpRequest();
        $this->requirePermission('super-content-access:manage-policies');

        return parent::beforeAction($action);
    }

    /**
     * Redirects the section root to the Channels list.
     *
     * @return Response Redirect response.
     */
    public function actionIndex(): Response
    {
        return $this->redirect('super-content-access/access/channels');
    }

    /**
     * Lists all channels with their current default access.
     *
     * @return Response Rendered channels list.
     */
    public function actionChannels(): Response
    {
        $policies = Plugin::getInstance()->getPolicies();
        $channels = [];

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            if ($section->type !== Section::TYPE_CHANNEL) {
                continue;
            }

            $principals = $policies->getForSection((int)$section->id);

            $channels[] = [
                'section' => $section,
                'restricted' => $principals !== null,
                'empty' => $principals !== null && $principals === [],
                'count' => $principals !== null ? count($principals) : 0,
            ];
        }

        $this->view->registerAssetBundle(AccessScreensAsset::class);

        return $this->renderTemplate('super-content-access/access/channels', [
            'title' => Craft::t('super-content-access', 'General Access'),
            'selectedItem' => 'channels',
            'commerceAvailable' => CommerceHelper::isAvailable(),
            'channels' => $channels,
        ]);
    }

    /**
     * Renders the default access editor for a single channel.
     *
     * @param string $section Section handle.
     *
     * @return Response Rendered channel editor.
     *
     * @throws NotFoundHttpException When the channel does not exist.
     */
    public function actionChannel(string $section): Response
    {
        $sectionModel = Craft::$app->getEntries()->getSectionByHandle($section);
        if ($sectionModel === null || $sectionModel->type !== Section::TYPE_CHANNEL) {
            throw new NotFoundHttpException('Channel not found.');
        }

        $principals = Plugin::getInstance()->getPolicies()->getForSection((int)$sectionModel->id);
        $value = $this->valueFromPrincipals($principals);

        $this->view->registerAssetBundle(AccessScreensAsset::class);
        $this->view->registerAssetBundle(AccessControlFieldAsset::class);

        return $this->renderTemplate('super-content-access/access/channel', [
            'title' => $sectionModel->name,
            'selectedItem' => 'channels',
            'commerceAvailable' => CommerceHelper::isAvailable(),
            'section' => $sectionModel,
            'entryCount' => (int)Entry::find()->section($sectionModel->handle)->status(null)->count(),
            'value' => $value,
            'groupOptions' => $this->groupOptions(),
            'selectedUsers' => $this->selectedUsers($value),
            'userElementType' => User::class,
        ]);
    }

    /**
     * Persists a channel's default access policy.
     *
     * @return Response|null Redirect on success, or null to re-render on error.
     *
     * @throws NotFoundHttpException When the channel does not exist.
     */
    public function actionSaveChannel(): ?Response
    {
        $this->requirePostRequest();

        $sectionId = (int)$this->request->getRequiredBodyParam('sectionId');
        $section = Craft::$app->getEntries()->getSectionById($sectionId);
        if ($section === null || $section->type !== Section::TYPE_CHANNEL) {
            throw new NotFoundHttpException('Channel not found.');
        }

        $policy = $this->request->getBodyParam('policy', []);
        $enabled = is_array($policy) && !empty($policy['enabled']) && $policy['enabled'] !== '0';

        $policies = Plugin::getInstance()->getPolicies();

        if (!$enabled) {
            $policies->deleteForSection($sectionId);
            Craft::$app->getSession()->setNotice(Craft::t('super-content-access', 'Channel access updated.'));

            return $this->redirectToPostedUrl();
        }

        try {
            $policies->saveForSection($sectionId, $this->principalsFromInput(is_array($policy) ? $policy : []));
        } catch (\Throwable $e) {
            Craft::$app->getSession()->setError($e->getMessage());

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('super-content-access', 'Channel access updated.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Lists all category groups with their current default access.
     *
     * @return Response Rendered category groups list.
     */
    public function actionCategories(): Response
    {
        $policies = Plugin::getInstance()->getPolicies();
        $groups = [];

        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $principals = $policies->getForGroup((int)$group->id);

            $groups[] = [
                'group' => $group,
                'restricted' => $principals !== null,
                'empty' => $principals !== null && $principals === [],
                'count' => $principals !== null ? count($principals) : 0,
            ];
        }

        $this->view->registerAssetBundle(AccessScreensAsset::class);

        return $this->renderTemplate('super-content-access/access/categories', [
            'title' => Craft::t('super-content-access', 'General Access'),
            'selectedItem' => 'categories',
            'commerceAvailable' => CommerceHelper::isAvailable(),
            'groups' => $groups,
        ]);
    }

    /**
     * Renders the default access editor for a single category group.
     *
     * @param string $group Category group handle.
     *
     * @return Response Rendered group editor.
     *
     * @throws NotFoundHttpException When the group does not exist.
     */
    public function actionCategory(string $group): Response
    {
        $groupModel = Craft::$app->getCategories()->getGroupByHandle($group);
        if ($groupModel === null) {
            throw new NotFoundHttpException('Category group not found.');
        }

        $principals = Plugin::getInstance()->getPolicies()->getForGroup((int)$groupModel->id);
        $value = $this->valueFromPrincipals($principals);

        $this->view->registerAssetBundle(AccessScreensAsset::class);
        $this->view->registerAssetBundle(AccessControlFieldAsset::class);

        return $this->renderTemplate('super-content-access/access/category', [
            'title' => $groupModel->name,
            'selectedItem' => 'categories',
            'commerceAvailable' => CommerceHelper::isAvailable(),
            'group' => $groupModel,
            'categoryCount' => (int)Category::find()->group($groupModel->handle)->status(null)->count(),
            'value' => $value,
            'groupOptions' => $this->groupOptions(),
            'selectedUsers' => $this->selectedUsers($value),
            'userElementType' => User::class,
        ]);
    }

    /**
     * Persists a category group's default access policy.
     *
     * @return Response|null Redirect on success, or null to re-render on error.
     *
     * @throws NotFoundHttpException When the group does not exist.
     */
    public function actionSaveCategory(): ?Response
    {
        $this->requirePostRequest();

        $groupId = (int)$this->request->getRequiredBodyParam('groupId');
        $group = Craft::$app->getCategories()->getGroupById($groupId);
        if ($group === null) {
            throw new NotFoundHttpException('Category group not found.');
        }

        $policy = $this->request->getBodyParam('policy', []);
        $enabled = is_array($policy) && !empty($policy['enabled']) && $policy['enabled'] !== '0';

        $policies = Plugin::getInstance()->getPolicies();

        if (!$enabled) {
            $policies->deleteForGroup($groupId);
            Craft::$app->getSession()->setNotice(Craft::t('super-content-access', 'Category group access updated.'));

            return $this->redirectToPostedUrl();
        }

        try {
            $policies->saveForGroup($groupId, $this->principalsFromInput(is_array($policy) ? $policy : []));
        } catch (\Throwable $e) {
            Craft::$app->getSession()->setError($e->getMessage());

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('super-content-access', 'Category group access updated.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Lists all Commerce product types with their current default access.
     *
     * @return Response Rendered product types list.
     *
     * @throws NotFoundHttpException When Commerce is unavailable.
     */
    public function actionProducts(): Response
    {
        $this->requireCommerce();

        /** @var \craft\commerce\Plugin $commerce */
        $commerce = \craft\commerce\Plugin::getInstance();
        $policies = Plugin::getInstance()->getPolicies();
        $types = [];

        foreach ($commerce->getProductTypes()->getAllProductTypes() as $type) {
            $principals = $policies->getForProductType((int)$type->id);

            $types[] = [
                'type' => $type,
                'restricted' => $principals !== null,
                'empty' => $principals !== null && $principals === [],
                'count' => $principals !== null ? count($principals) : 0,
            ];
        }

        $this->view->registerAssetBundle(AccessScreensAsset::class);

        return $this->renderTemplate('super-content-access/access/products', [
            'title' => Craft::t('super-content-access', 'General Access'),
            'selectedItem' => 'products',
            'commerceAvailable' => true,
            'types' => $types,
        ]);
    }

    /**
     * Renders the default access editor for a single product type.
     *
     * @param string $type Product type handle.
     *
     * @return Response Rendered product type editor.
     *
     * @throws NotFoundHttpException When Commerce is unavailable or the type does not exist.
     */
    public function actionProduct(string $type): Response
    {
        $this->requireCommerce();

        /** @var \craft\commerce\Plugin $commerce */
        $commerce = \craft\commerce\Plugin::getInstance();
        $typeModel = $commerce->getProductTypes()->getProductTypeByHandle($type);
        if ($typeModel === null) {
            throw new NotFoundHttpException('Product type not found.');
        }

        $principals = Plugin::getInstance()->getPolicies()->getForProductType((int)$typeModel->id);
        $value = $this->valueFromPrincipals($principals);

        $this->view->registerAssetBundle(AccessScreensAsset::class);
        $this->view->registerAssetBundle(AccessControlFieldAsset::class);

        $productClass = 'craft\\commerce\\elements\\Product';
        $productCount = 0;
        if (class_exists($productClass)) {
            $productCount = (int)$productClass::find()->type($typeModel->handle)->status(null)->count();
        }

        return $this->renderTemplate('super-content-access/access/product', [
            'title' => $typeModel->name,
            'selectedItem' => 'products',
            'commerceAvailable' => true,
            'type' => $typeModel,
            'productCount' => $productCount,
            'value' => $value,
            'groupOptions' => $this->groupOptions(),
            'selectedUsers' => $this->selectedUsers($value),
            'userElementType' => User::class,
        ]);
    }

    /**
     * Persists a product type's default access policy.
     *
     * @return Response|null Redirect on success, or null to re-render on error.
     *
     * @throws NotFoundHttpException When Commerce is unavailable or the type does not exist.
     */
    public function actionSaveProduct(): ?Response
    {
        $this->requirePostRequest();
        $this->requireCommerce();

        /** @var \craft\commerce\Plugin $commerce */
        $commerce = \craft\commerce\Plugin::getInstance();
        $productTypeId = (int)$this->request->getRequiredBodyParam('productTypeId');
        $type = $commerce->getProductTypes()->getProductTypeById($productTypeId);
        if ($type === null) {
            throw new NotFoundHttpException('Product type not found.');
        }

        $policy = $this->request->getBodyParam('policy', []);
        $enabled = is_array($policy) && !empty($policy['enabled']) && $policy['enabled'] !== '0';

        $policies = Plugin::getInstance()->getPolicies();

        if (!$enabled) {
            $policies->deleteForProductType($productTypeId);
            Craft::$app->getSession()->setNotice(Craft::t('super-content-access', 'Product type access updated.'));

            return $this->redirectToPostedUrl();
        }

        try {
            $policies->saveForProductType($productTypeId, $this->principalsFromInput(is_array($policy) ? $policy : []));
        } catch (\Throwable $e) {
            Craft::$app->getSession()->setError($e->getMessage());

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('super-content-access', 'Product type access updated.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Ensures Commerce is available for product General Access screens.
     *
     * @return void Nothing is returned.
     *
     * @throws NotFoundHttpException When Commerce is not available.
     */
    private function requireCommerce(): void
    {
        if (!CommerceHelper::isAvailable()) {
            throw new NotFoundHttpException('Craft Commerce is not available.');
        }
    }

    /**
     * Builds an editor value object from stored principals.
     *
     * @param PolicyPrincipal[]|null $principals Stored principals, or null.
     *
     * @return AccessControlValue Value for the shared policy editor.
     */
    private function valueFromPrincipals(?array $principals): AccessControlValue
    {
        if ($principals === null) {
            return new AccessControlValue();
        }

        $groupIds = [];
        $userIds = [];

        foreach ($principals as $principal) {
            if ($principal->type === PrincipalType::GROUP) {
                $groupIds[] = (int)$principal->identifier;
            } elseif ($principal->type === PrincipalType::USER) {
                $userIds[] = (int)$principal->identifier;
            }
        }

        return new AccessControlValue(
            enabled: true,
            groupIds: array_values(array_unique(array_filter($groupIds))),
            userIds: array_values(array_unique(array_filter($userIds))),
        );
    }

    /**
     * Converts posted editor data into domain principals.
     *
     * @param array $policy Posted policy data.
     *
     * @return PolicyPrincipal[] Principals to persist.
     */
    private function principalsFromInput(array $policy): array
    {
        $principals = [];

        foreach ($this->intList($policy['groupIds'] ?? []) as $groupId) {
            $principals[] = new PolicyPrincipal(PrincipalType::GROUP, (string)$groupId);
        }

        foreach ($this->intList($policy['userIds'] ?? []) as $userId) {
            $principals[] = new PolicyPrincipal(PrincipalType::USER, (string)$userId);
        }

        return $principals;
    }

    /**
     * Builds user group options for the editor.
     *
     * @return array<int, array{label: string, value: string}> Group options.
     */
    private function groupOptions(): array
    {
        $options = [];

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            $options[] = [
                'label' => $group->name,
                'value' => (string)$group->id,
            ];
        }

        return $options;
    }

    /**
     * Loads the selected user elements for the editor.
     *
     * @param AccessControlValue $value Editor value.
     *
     * @return User[] Selected users.
     */
    private function selectedUsers(AccessControlValue $value): array
    {
        if ($value->userIds === []) {
            return [];
        }

        return User::find()
            ->id($value->userIds)
            ->status(null)
            ->fixedOrder()
            ->all();
    }

    /**
     * Normalizes a submitted list into unique positive integers.
     *
     * @param mixed $input Raw submitted value.
     *
     * @return int[] Filtered integer list.
     */
    private function intList(mixed $input): array
    {
        if (!is_array($input)) {
            $input = $input === '' || $input === null ? [] : [$input];
        }

        $ids = [];
        foreach ($input as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
