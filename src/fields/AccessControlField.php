<?php
/**
 * Super Content Access custom field type.
 *
 * The Access Control field is the editing interface for an element's Access
 * Policy. It intentionally stores nothing in Craft field content (dbType is
 * null); all reads and writes go through the Policy Service into the plugin's
 * own tables. This preserves the domain rule that authorization rules never
 * live in Craft content while still giving editors an inline field UI.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\fields;

use amici\SuperContentAccess\assetbundles\AccessControlFieldAsset;
use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\fields\data\AccessControlValue;
use amici\SuperContentAccess\Plugin;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\User;
use craft\helpers\ElementHelper;
use Throwable;
use yii\base\Model;

/**
 * Craft field type that manages an element's Access Policy.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AccessControlField extends Field
{
    // Static Methods
    // =========================================================================

    /**
     * Returns the label shown in Craft's field type selector.
     *
     * @return string Translated field type name.
     */
    public static function displayName(): string
    {
        return Craft::t('super-content-access', 'Access Control [Super Content Access]');
    }

    /**
     * Returns the Craft icon name used for the field type.
     *
     * @return string Icon handle.
     */
    public static function icon(): string
    {
        return 'lock';
    }

    /**
     * Stores no content in Craft; the plugin manages its own tables.
     *
     * @return array|string|null Always null.
     */
    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * Defines the normalized PHP type returned by this field.
     *
     * @return string Fully qualified value object class name.
     */
    public static function phpType(): string
    {
        return sprintf('\\%s', AccessControlValue::class);
    }

    // Public Methods
    // =========================================================================

    /**
     * Normalizes stored/submitted data into an AccessControlValue.
     *
     * @param mixed $value Raw value from storage load or form submission.
     * @param ?ElementInterface $element Owning element, when available.
     *
     * @return mixed Normalized field value.
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof AccessControlValue) {
            return $value;
        }

        // An array means the value came from a posted form.
        if (is_array($value)) {
            return new AccessControlValue(
                enabled: !empty($value['enabled']) && $value['enabled'] !== '0',
                groupIds: $this->intList($value['groupIds'] ?? []),
                userIds: $this->intList($value['userIds'] ?? []),
                submitted: true,
            );
        }

        // Otherwise load the authoritative policy from the plugin's tables.
        return $this->valueFromStorage($element);
    }

    /**
     * Never serializes into Craft content storage.
     *
     * @param mixed $value Normalized or raw field value.
     * @param ?ElementInterface $element Owning element, when available.
     *
     * @return mixed Always null.
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return null;
    }

    /**
     * Determines whether the field should be considered empty.
     *
     * @param mixed $value Normalized or raw field value.
     * @param ElementInterface $element Owning element.
     *
     * @return bool True when the entry is not restricted.
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        return !$value instanceof AccessControlValue || !$value->enabled;
    }

    /**
     * Registers element-level validation for the selected audiences.
     *
     * @return array Validation method names.
     */
    public function getElementValidationRules(): array
    {
        return ['validateAudiences'];
    }

    /**
     * Validates that selected groups and users still exist.
     *
     * @param ElementInterface $element Element containing this field.
     *
     * @return void Nothing is returned.
     */
    public function validateAudiences(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        if (!$value instanceof AccessControlValue || !$value->enabled) {
            return;
        }

        foreach ($value->groupIds as $groupId) {
            if (Craft::$app->getUserGroups()->getGroupById($groupId) === null) {
                $this->addFieldError($element, Craft::t('super-content-access', 'A selected user group no longer exists.'));
                return;
            }
        }

        if ($value->userIds !== []) {
            $found = User::find()->id($value->userIds)->status(null)->count();
            if ((int)$found !== count($value->userIds)) {
                $this->addFieldError($element, Craft::t('super-content-access', 'A selected user no longer exists.'));
            }
        }
    }

    /**
     * Persists the Access Policy after the element is saved.
     *
     * Only writes when the value originated from a submission, so canonical
     * saves that reload an unedited value can never blank out the policy.
     *
     * @param ElementInterface $element Saved element.
     * @param bool $isNew Whether the element is newly created.
     *
     * @return void Nothing is returned.
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        parent::afterElementSave($element, $isNew);

        if (ElementHelper::isRevision($element)) {
            return;
        }

        $value = $element->getFieldValue($this->handle);
        if (!$value instanceof AccessControlValue || !$value->submitted) {
            return;
        }

        $elementId = $this->canonicalId($element);
        if ($elementId === null) {
            return;
        }

        $policies = Plugin::getInstance()->getPolicies();

        try {
            if (!$value->enabled) {
                $policies->deleteForElement($elementId);
                return;
            }

            $policies->saveForElement($elementId, $this->principalsFromValue($value));
        } catch (Throwable $e) {
            Craft::error(
                sprintf('Unable to save access policy for element %d: %s', $elementId, $e->getMessage()),
                __METHOD__
            );
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Renders the Control Panel input for an element edit page.
     *
     * @param mixed $value Normalized or raw field value.
     * @param ?ElementInterface $element Owning element, when available.
     * @param bool $inline Whether Craft is rendering the input inline.
     *
     * @return string Input HTML.
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $value = $this->normalizeValue($value, $element);

        Craft::$app->getView()->registerAssetBundle(AccessControlFieldAsset::class);

        return Craft::$app->getView()->renderTemplate('super-content-access/_field/input', [
            'field' => $this,
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'value' => $value,
            'groupOptions' => $this->groupOptions(),
            'selectedUsers' => $this->selectedUsers($value),
            'userElementType' => User::class,
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Builds a value object from the stored policy for an element.
     *
     * @param ?ElementInterface $element Owning element, when available.
     *
     * @return AccessControlValue Loaded value, or an empty default.
     */
    private function valueFromStorage(?ElementInterface $element): AccessControlValue
    {
        $elementId = $this->canonicalId($element);
        if ($elementId === null) {
            return new AccessControlValue();
        }

        $policy = Plugin::getInstance()->getPolicies()->getForElementId($elementId);
        if (!$policy instanceof AccessPolicy) {
            return new AccessControlValue();
        }

        $groupIds = [];
        $userIds = [];

        foreach ($policy->principals as $principal) {
            switch ($principal->type) {
                case PrincipalType::GROUP:
                    $groupIds[] = (int)$principal->identifier;
                    break;
                case PrincipalType::USER:
                    $userIds[] = (int)$principal->identifier;
                    break;
                default:
                    break;
            }
        }

        return new AccessControlValue(
            enabled: true,
            groupIds: array_values(array_unique(array_filter($groupIds))),
            userIds: array_values(array_unique(array_filter($userIds))),
            submitted: false,
        );
    }

    /**
     * Converts a value object into domain principals.
     *
     * @param AccessControlValue $value Field value.
     *
     * @return PolicyPrincipal[] Principals for persistence.
     */
    private function principalsFromValue(AccessControlValue $value): array
    {
        $principals = [];

        foreach ($value->groupIds as $groupId) {
            $principals[] = new PolicyPrincipal(PrincipalType::GROUP, (string)$groupId);
        }

        foreach ($value->userIds as $userId) {
            $principals[] = new PolicyPrincipal(PrincipalType::USER, (string)$userId);
        }

        return $principals;
    }

    /**
     * Resolves the canonical element ID used as the policy key.
     *
     * @param ?ElementInterface $element Owning element, when available.
     *
     * @return ?int Canonical element ID, or null when unavailable.
     */
    private function canonicalId(?ElementInterface $element): ?int
    {
        if ($element === null || !$element->id || ElementHelper::isRevision($element)) {
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
     * Builds user group options for the input template.
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
     * Loads the selected user elements for the element selector.
     *
     * @param AccessControlValue $value Field value.
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

    /**
     * Adds a validation error to the owning element when possible.
     *
     * @param ElementInterface $element Element being validated.
     * @param string $message Error message.
     *
     * @return void Nothing is returned.
     */
    private function addFieldError(ElementInterface $element, string $message): void
    {
        if ($element instanceof Model) {
            $element->addError($this->handle, $message);
        }
    }
}
