<?php
/**
 * Application service for Access Policy CRUD and validation.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\services;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\contracts\PolicyServiceInterface;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;
use amici\SuperContentAccess\events\PolicyEvent;
use amici\SuperContentAccess\Plugin;
use amici\SuperContentAccess\repositories\PolicyRepository;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\elements\User;
use yii\base\InvalidArgumentException;

/**
 * Application service for Access Policy CRUD and validation.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class PolicyService extends Component implements PolicyServiceInterface
{
    /**
     * Event fired before an access policy is saved (element or scope default).
     *
     * Handlers receive a {@see PolicyEvent}. Set `$event->isValid = false` to abort.
     */
    public const EVENT_BEFORE_SAVE_POLICY = 'beforeSavePolicy';

    /**
     * Event fired after an access policy is saved successfully.
     *
     * Handlers receive a {@see PolicyEvent} with the persisted identifiers / policy.
     */
    public const EVENT_AFTER_SAVE_POLICY = 'afterSavePolicy';

    /**
     * Event fired before an access policy is deleted (element or scope default).
     *
     * Handlers receive a {@see PolicyEvent}. Set `$event->isValid = false` to abort.
     */
    public const EVENT_BEFORE_DELETE_POLICY = 'beforeDeletePolicy';

    /**
     * Event fired after an access policy is deleted successfully.
     *
     * Handlers receive a {@see PolicyEvent} describing what was removed.
     */
    public const EVENT_AFTER_DELETE_POLICY = 'afterDeletePolicy';

    /**
     * Loads the access policy for an element.
     *
     * @param ElementInterface $element Element to look up.
     *
     * @return AccessPolicy|null The policy, or null when none exists.
     */
    public function getForElement(ElementInterface $element): ?AccessPolicy
    {
        if ($element->id === null) {
            return null;
        }

        return $this->getForElementId((int)$element->id);
    }

    /**
     * Loads the access policy for an element ID.
     *
     * @param int $elementId Element ID to look up.
     *
     * @return AccessPolicy|null The policy, or null when none exists.
     */
    public function getForElementId(int $elementId): ?AccessPolicy
    {
        return $this->repository()->findByElementId($elementId);
    }

    /**
     * Persists principals for an element.
     *
     * @param int $elementId Element ID to protect.
     * @param PolicyPrincipal[] $principals Principals to save.
     *
     * @return AccessPolicy The saved policy.
     */
    public function saveForElement(int $elementId, array $principals): AccessPolicy
    {
        $this->validatePrincipals($principals);

        $event = new PolicyEvent([
            'sender' => $this,
            'elementId' => $elementId,
            'principals' => $principals,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE_POLICY, $event);

        if (!$event->isValid) {
            throw new \RuntimeException('Access policy save was cancelled.');
        }

        $policy = $this->repository()->save($elementId, $principals);
        $this->resetQueryMemo();

        $this->trigger(self::EVENT_AFTER_SAVE_POLICY, new PolicyEvent([
            'sender' => $this,
            'elementId' => $elementId,
            'principals' => $principals,
            'policy' => $policy,
        ]));

        return $policy;
    }

    /**
     * Deletes the access policy for an element.
     *
     * @param int $elementId Element ID whose policy should be removed.
     *
     * @return bool True when a policy was deleted.
     */
    public function deleteForElement(int $elementId): bool
    {
        $event = new PolicyEvent([
            'sender' => $this,
            'elementId' => $elementId,
        ]);
        $this->trigger(self::EVENT_BEFORE_DELETE_POLICY, $event);

        if (!$event->isValid) {
            return false;
        }

        $deleted = $this->repository()->deleteByElementId($elementId);

        if ($deleted) {
            $this->resetQueryMemo();
            $this->trigger(self::EVENT_AFTER_DELETE_POLICY, new PolicyEvent([
                'sender' => $this,
                'elementId' => $elementId,
            ]));
        }

        return $deleted;
    }

    /**
     * Loads the principals for a section (channel or structure) default policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function getForSection(int $sectionId): ?array
    {
        return $this->repository()->findBySectionId($sectionId);
    }

    /**
     * Saves a section (channel or structure) default policy.
     *
     * @param int $sectionId Section ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForSection(int $sectionId, array $principals): void
    {
        $this->saveScopePolicy('sectionId', $sectionId, $principals, fn() => $this->repository()->saveForSection($sectionId, $principals));
    }

    /**
     * Removes a section (channel or structure) default policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteForSection(int $sectionId): bool
    {
        return $this->deleteScopePolicy('sectionId', $sectionId, fn(): bool => $this->repository()->deleteBySectionId($sectionId));
    }

    /**
     * Loads the principals for a category-group default policy.
     *
     * @param int $groupId Category group ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function getForGroup(int $groupId): ?array
    {
        return $this->repository()->findByGroupId($groupId);
    }

    /**
     * Saves a category-group default policy.
     *
     * @param int $groupId Category group ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForGroup(int $groupId, array $principals): void
    {
        $this->saveScopePolicy('groupId', $groupId, $principals, fn() => $this->repository()->saveForGroup($groupId, $principals));
    }

    /**
     * Removes a category-group default policy.
     *
     * @param int $groupId Category group ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteForGroup(int $groupId): bool
    {
        return $this->deleteScopePolicy('groupId', $groupId, fn(): bool => $this->repository()->deleteByGroupId($groupId));
    }

    /**
     * Loads the principals for a Commerce product-type default policy.
     *
     * @param int $productTypeId Product type ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function getForProductType(int $productTypeId): ?array
    {
        return $this->repository()->findByProductTypeId($productTypeId);
    }

    /**
     * Saves a Commerce product-type default policy.
     *
     * @param int $productTypeId Product type ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForProductType(int $productTypeId, array $principals): void
    {
        $this->saveScopePolicy('productTypeId', $productTypeId, $principals, fn() => $this->repository()->saveForProductType($productTypeId, $principals));
    }

    /**
     * Removes a Commerce product-type default policy.
     *
     * @param int $productTypeId Product type ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteForProductType(int $productTypeId): bool
    {
        return $this->deleteScopePolicy('productTypeId', $productTypeId, fn(): bool => $this->repository()->deleteByProductTypeId($productTypeId));
    }

    /**
     * Saves a default-scope policy with before/after events.
     *
     * @param string $idProperty PolicyEvent property (sectionId, groupId, productTypeId).
     * @param int $id Scope ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     * @param callable(): void $persist Repository persist callback.
     *
     * @return void Nothing is returned.
     */
    private function saveScopePolicy(string $idProperty, int $id, array $principals, callable $persist): void
    {
        $this->validatePrincipals($principals);

        $event = new PolicyEvent([
            'sender' => $this,
            $idProperty => $id,
            'principals' => $principals,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE_POLICY, $event);

        if (!$event->isValid) {
            throw new \RuntimeException('Access policy save was cancelled.');
        }

        $persist();
        $this->resetQueryMemo();

        $this->trigger(self::EVENT_AFTER_SAVE_POLICY, new PolicyEvent([
            'sender' => $this,
            $idProperty => $id,
            'principals' => $principals,
        ]));
    }

    /**
     * Deletes a default-scope policy with before/after events.
     *
     * @param string $idProperty PolicyEvent property (sectionId, groupId, productTypeId).
     * @param int $id Scope ID.
     * @param callable(): bool $delete Repository delete callback.
     *
     * @return bool Whether a policy was deleted.
     */
    private function deleteScopePolicy(string $idProperty, int $id, callable $delete): bool
    {
        $event = new PolicyEvent([
            'sender' => $this,
            $idProperty => $id,
        ]);
        $this->trigger(self::EVENT_BEFORE_DELETE_POLICY, $event);

        if (!$event->isValid) {
            return false;
        }

        $deleted = $delete();
        if ($deleted) {
            $this->resetQueryMemo();
            $this->trigger(self::EVENT_AFTER_DELETE_POLICY, new PolicyEvent([
                'sender' => $this,
                $idProperty => $id,
            ]));
        }

        return $deleted;
    }

    /**
     * Clears request-scoped query integrator memos after policy changes.
     *
     * @return void Nothing is returned.
     */
    private function resetQueryMemo(): void
    {
        Plugin::getInstance()->getElementQueryIntegrator()->resetMemo();
    }

    /**
     * Validates that principals are well-formed and reference existing users/groups.
     *
     * @param PolicyPrincipal[] $principals Principals to validate.
     *
     * @return void Nothing is returned.
     *
     * @throws InvalidArgumentException When a principal is invalid.
     */
    public function validatePrincipals(array $principals): void
    {
        foreach ($principals as $principal) {
            if (!$principal instanceof PolicyPrincipal) {
                throw new InvalidArgumentException('Invalid policy principal.');
            }

            if (!in_array($principal->type, PrincipalType::ALL, true)) {
                throw new InvalidArgumentException(
                    Craft::t('super-content-access', 'Unsupported principal type “{type}”.', [
                        'type' => $principal->type,
                    ])
                );
            }

            if ($principal->type === PrincipalType::USER) {
                $user = User::find()->id((int)$principal->identifier)->status(null)->one();
                if (!$user) {
                    throw new InvalidArgumentException(
                        Craft::t('super-content-access', 'User {id} does not exist.', [
                            'id' => $principal->identifier,
                        ])
                    );
                }
            }

            if ($principal->type === PrincipalType::GROUP) {
                $group = Craft::$app->getUserGroups()->getGroupById((int)$principal->identifier);
                if (!$group) {
                    throw new InvalidArgumentException(
                        Craft::t('super-content-access', 'User group {id} does not exist.', [
                            'id' => $principal->identifier,
                        ])
                    );
                }
            }
        }
    }

    /**
     * Returns the policy repository used for persistence.
     *
     * @return PolicyRepository The policy repository instance.
     */
    private function repository(): PolicyRepository
    {
        return Plugin::getInstance()->getPolicyRepository();
    }
}
