<?php
/**
 * Public contract for Access Policy management.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\domain\contracts;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use craft\base\ElementInterface;

/**
 * Public contract for Access Policy management.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
interface PolicyServiceInterface
{
    /**
     * Loads the access policy for an element.
     *
     * @param ElementInterface $element Element to look up.
     *
     * @return AccessPolicy|null The policy, or null when none exists.
     */
    public function getForElement(ElementInterface $element): ?AccessPolicy;

    /**
     * Loads the access policy for an element ID.
     *
     * @param int $elementId Element ID to look up.
     *
     * @return AccessPolicy|null The policy, or null when none exists.
     */
    public function getForElementId(int $elementId): ?AccessPolicy;

    /**
     * Persists principals for an element.
     *
     * @param int $elementId Element ID to protect.
     * @param PolicyPrincipal[] $principals Principals to save.
     *
     * @return AccessPolicy The saved policy.
     */
    public function saveForElement(int $elementId, array $principals): AccessPolicy;

    /**
     * Deletes the access policy for an element.
     *
     * @param int $elementId Element ID whose policy should be removed.
     *
     * @return bool True when a policy was deleted.
     */
    public function deleteForElement(int $elementId): bool;

    /**
     * Loads the principals for a section (channel or structure) default policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function getForSection(int $sectionId): ?array;

    /**
     * Saves a section (channel or structure) default policy.
     *
     * @param int $sectionId Section ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForSection(int $sectionId, array $principals): void;

    /**
     * Removes a section (channel or structure) default policy.
     *
     * @param int $sectionId Section ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteForSection(int $sectionId): bool;

    /**
     * Loads the principals for a category-group default policy.
     *
     * @param int $groupId Category group ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function getForGroup(int $groupId): ?array;

    /**
     * Saves a category-group default policy.
     *
     * @param int $groupId Category group ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForGroup(int $groupId, array $principals): void;

    /**
     * Removes a category-group default policy.
     *
     * @param int $groupId Category group ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteForGroup(int $groupId): bool;

    /**
     * Loads the principals for a Commerce product-type default policy.
     *
     * @param int $productTypeId Product type ID.
     *
     * @return PolicyPrincipal[]|null Principals, or null when no policy exists.
     */
    public function getForProductType(int $productTypeId): ?array;

    /**
     * Saves a Commerce product-type default policy.
     *
     * @param int $productTypeId Product type ID.
     * @param PolicyPrincipal[] $principals Principals to persist.
     *
     * @return void Nothing is returned.
     */
    public function saveForProductType(int $productTypeId, array $principals): void;

    /**
     * Removes a Commerce product-type default policy.
     *
     * @param int $productTypeId Product type ID.
     *
     * @return bool Whether a policy was deleted.
     */
    public function deleteForProductType(int $productTypeId): bool;
}
