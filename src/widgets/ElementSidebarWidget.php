<?php
/**
 * Read-only Access Policy summary for the element editor sidebar.
 *
 * This widget never edits authorization state. Editing happens through the
 * Access Control field. The sidebar only reports the current policy so editors
 * can see an element's access at a glance.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\widgets;

use amici\SuperContentAccess\assetbundles\ElementSidebarWidgetAsset;
use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\fields\AccessControlField;
use amici\SuperContentAccess\helpers\CommerceHelper;
use amici\SuperContentAccess\helpers\StructurePolicyHelper;
use amici\SuperContentAccess\Plugin;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;

/**
 * Renders the read-only Super Content Access sidebar summary.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class ElementSidebarWidget extends Component
{
    /**
     * Whether the widget can render for the given element.
     *
     * @param ElementInterface $element Candidate element.
     *
     * @return bool True for saved entries, categories, and products.
     */
    public function supports(ElementInterface $element): bool
    {
        if ($element->id === null) {
            return false;
        }

        if ($element instanceof Entry || $element instanceof Category) {
            return true;
        }

        $productClass = 'craft\\commerce\\elements\\Product';

        return CommerceHelper::isAvailable() && class_exists($productClass) && $element instanceof $productClass;
    }

    /**
     * Renders the read-only summary HTML.
     *
     * @param ElementInterface $element Element being edited.
     * @param bool $static Whether the sidebar is rendered statically.
     *
     * @return string Summary HTML, or an empty string when unsupported.
     */
    public function render(ElementInterface $element, bool $static = false): string
    {
        if (!$this->supports($element)) {
            return '';
        }

        $elementId = $this->canonicalId($element);
        if ($elementId === null) {
            return '';
        }

        $policies = Plugin::getInstance()->getPolicies();
        $policy = $policies->getForElementId($elementId);
        $ancestor = null;
        $scope = null;

        if ($policy instanceof AccessPolicy) {
            $principals = $policy->principals;
            $restricted = true;
            $source = 'element';
        } else {
            if ($element instanceof Entry) {
                $ancestor = StructurePolicyHelper::nearestAncestorPolicy($elementId);
            }

            if ($ancestor !== null) {
                $principals = $ancestor['policy']->principals;
                $restricted = true;
                $source = 'parent';
                $scope = $this->parentScope($ancestor['ancestorId']);
            } else {
                $scope = $this->resolveScope($element, $policies);
                if ($scope !== null) {
                    $principals = $scope['principals'];
                    $restricted = true;
                    $source = $scope['source'];
                } else {
                    $principals = [];
                    $restricted = false;
                    $source = 'none';
                }
            }
        }

        // Only surface the summary where access is manageable (field present),
        // an element policy exists, or a scope default applies.
        if ($source === 'none' && !$this->hasAccessControlField($element)) {
            return '';
        }

        Craft::$app->getView()->registerAssetBundle(ElementSidebarWidgetAsset::class);

        $groupNames = $this->groupNames($principals);
        $userNames = $this->userNames($principals);

        return Craft::$app->getView()->renderTemplate('super-content-access/_widgets/element-sidebar', [
            'restricted' => $restricted,
            'empty' => $groupNames === [] && $userNames === [],
            'groupNames' => $groupNames,
            'userNames' => $userNames,
            'source' => $source,
            'elementLabel' => $this->elementLabel($element),
            'scopeName' => $scope['name'] ?? null,
            'scopeUrl' => $scope['url'] ?? null,
            'scopeKind' => $scope['kind'] ?? null,
        ]);
    }

    /**
     * Builds sidebar scope details for an inherited parent entry policy.
     *
     * @param int $ancestorId Ancestor entry element ID.
     *
     * @return array{principals: PolicyPrincipal[], source: string, name: string, url: string|null, kind: string}
     */
    private function parentScope(int $ancestorId): array
    {
        $ancestor = StructurePolicyHelper::ancestorEntry($ancestorId);
        $name = $ancestor !== null
            ? (string)$ancestor
            : Craft::t('super-content-access', 'Parent entry');

        $url = null;
        if ($ancestor !== null && $ancestor->getCpEditUrl()) {
            $url = $ancestor->getCpEditUrl();
        }

        return [
            'principals' => [],
            'source' => 'parent',
            'name' => $name,
            'url' => $url,
            'kind' => Craft::t('super-content-access', 'parent entry'),
        ];
    }

    /**
     * Resolves inherited default-scope policy details for the element.
     *
     * @param ElementInterface $element Element being edited.
     * @param \amici\SuperContentAccess\services\PolicyService $policies Policy service.
     *
     * @return array{principals: PolicyPrincipal[], source: string, name: string, url: string, kind: string}|null
     */
    private function resolveScope(ElementInterface $element, $policies): ?array
    {
        if ($element instanceof Entry) {
            $section = $element->getSection();
            if ($section === null) {
                return null;
            }

            $principals = $policies->getForSection((int)$section->id);
            if ($principals === null) {
                return null;
            }

            $kind = $section->type === Section::TYPE_STRUCTURE
                ? Craft::t('super-content-access', 'structure')
                : Craft::t('super-content-access', 'channel');

            return [
                'principals' => $principals,
                'source' => 'channel',
                'name' => $section->name,
                'url' => UrlHelper::cpUrl('super-content-access/access/channels/' . $section->handle),
                'kind' => $kind,
            ];
        }

        if ($element instanceof Category) {
            $group = $element->getGroup();
            if ($group === null) {
                return null;
            }

            $principals = $policies->getForGroup((int)$group->id);
            if ($principals === null) {
                return null;
            }

            return [
                'principals' => $principals,
                'source' => 'group',
                'name' => $group->name,
                'url' => UrlHelper::cpUrl('super-content-access/access/categories/' . $group->handle),
                'kind' => Craft::t('super-content-access', 'category group'),
            ];
        }

        $productClass = 'craft\\commerce\\elements\\Product';
        if (CommerceHelper::isAvailable() && class_exists($productClass) && $element instanceof $productClass) {
            $type = method_exists($element, 'getType') ? $element->getType() : null;
            if ($type === null || !isset($type->id)) {
                return null;
            }

            $principals = $policies->getForProductType((int)$type->id);
            if ($principals === null) {
                return null;
            }

            return [
                'principals' => $principals,
                'source' => 'productType',
                'name' => (string)$type->name,
                'url' => UrlHelper::cpUrl('super-content-access/access/products/' . $type->handle),
                'kind' => Craft::t('super-content-access', 'product type'),
            ];
        }

        return null;
    }

    /**
     * Human label for the element type in sidebar copy.
     *
     * @param ElementInterface $element Element being edited.
     *
     * @return string Localized element type label.
     */
    private function elementLabel(ElementInterface $element): string
    {
        if ($element instanceof Entry) {
            return Craft::t('app', 'entry');
        }

        if ($element instanceof Category) {
            return Craft::t('app', 'category');
        }

        return Craft::t('app', 'product');
    }

    /**
     * Whether the element's field layout includes the Access Control field.
     *
     * @param ElementInterface $element Element being edited.
     *
     * @return bool True when the field is present in the layout.
     */
    private function hasAccessControlField(ElementInterface $element): bool
    {
        $fieldLayout = $element->getFieldLayout();
        if ($fieldLayout === null) {
            return false;
        }

        foreach ($fieldLayout->getCustomFields() as $field) {
            if ($field instanceof AccessControlField) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolves the canonical element ID used to look up the policy.
     *
     * @param ElementInterface $element Element being edited.
     *
     * @return ?int Canonical element ID, or null when unavailable.
     */
    private function canonicalId(ElementInterface $element): ?int
    {
        if (!$element->id || ElementHelper::isRevision($element)) {
            return null;
        }

        if (method_exists($element, 'getCanonicalId')) {
            $canonicalId = (int)$element->getCanonicalId();
            if ($canonicalId > 0) {
                return $canonicalId;
            }
        }

        return (int)$element->id;
    }

    /**
     * Returns the display names of allowed user groups.
     *
     * @param PolicyPrincipal[] $principals Effective principals.
     *
     * @return string[] Group names.
     */
    private function groupNames(array $principals): array
    {
        $names = [];
        $groups = Craft::$app->getUserGroups();

        foreach ($principals as $principal) {
            if ($principal->type !== PrincipalType::GROUP) {
                continue;
            }

            $group = $groups->getGroupById((int)$principal->identifier);
            $names[] = $group?->name ?? Craft::t('super-content-access', 'Unknown group');
        }

        return $names;
    }

    /**
     * Returns the display names of allowed users.
     *
     * @param PolicyPrincipal[] $principals Effective principals.
     *
     * @return string[] User names.
     */
    private function userNames(array $principals): array
    {
        $ids = [];
        foreach ($principals as $principal) {
            if ($principal->type === PrincipalType::USER) {
                $ids[] = (int)$principal->identifier;
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        $names = [];
        foreach (User::find()->id($ids)->status(null)->all() as $user) {
            $names[] = (string)$user;
        }

        return $names;
    }
}
