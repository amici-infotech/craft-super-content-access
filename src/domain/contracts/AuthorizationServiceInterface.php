<?php
/**
 * Public contract for authorization evaluation helpers.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\domain\contracts;

use amici\SuperContentAccess\domain\AuthorizationContext;
use craft\base\ElementInterface;

/**
 * Public contract for authorization evaluation helpers.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
interface AuthorizationServiceInterface
{
    /**
     * Returns the authorization context for the current request.
     *
     * @return AuthorizationContext The current authorization context.
     */
    public function getContext(): AuthorizationContext;

    /**
     * Whether the given element is accessible in the current context.
     *
     * @param ElementInterface $element Element to evaluate.
     * @param AuthorizationContext|null $context Optional context override.
     *
     * @return bool True when access is allowed.
     */
    public function canAccessElement(ElementInterface $element, ?AuthorizationContext $context = null): bool;

    /**
     * Whether the given element ID is accessible in the current context.
     *
     * @param int $elementId Element ID to evaluate.
     * @param AuthorizationContext|null $context Optional context override.
     *
     * @return bool True when access is allowed.
     */
    public function canAccessElementId(int $elementId, ?AuthorizationContext $context = null): bool;
}
