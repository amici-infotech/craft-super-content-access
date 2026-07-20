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
use amici\SuperContentAccess\Plugin;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;

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
     * @return bool True for saved entries.
     */
    public function supports(ElementInterface $element): bool
    {
        return $element instanceof Entry && $element->id !== null;
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

        // Effective access resolves entry-level policy first, then the channel
        // (general access) default, matching how the query layer enforces it.
        $section = $element instanceof Entry ? $element->getSection() : null;
        $sectionPrincipals = $section !== null ? $policies->getForSection((int)$section->id) : null;

        if ($policy instanceof AccessPolicy) {
            $principals = $policy->principals;
            $restricted = true;
            $source = 'entry';
        } elseif ($sectionPrincipals !== null) {
            $principals = $sectionPrincipals;
            $restricted = true;
            $source = 'channel';
        } else {
            $principals = [];
            $restricted = false;
            $source = 'none';
        }

        // Only surface the summary where access is manageable (field present),
        // an entry policy exists, or a channel default applies.
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
            'channelName' => $section?->name,
            'channelUrl' => $section !== null
                ? UrlHelper::cpUrl('super-content-access/access/channels/' . $section->handle)
                : null,
        ]);
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
