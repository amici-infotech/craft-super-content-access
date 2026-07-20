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
}
