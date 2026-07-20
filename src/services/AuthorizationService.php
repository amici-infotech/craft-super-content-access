<?php
/**
 * Authorization helpers for single-element access checks.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\services;

use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\contracts\AuthorizationServiceInterface;
use amici\SuperContentAccess\Plugin;
use craft\base\Component;
use craft\base\ElementInterface;

/**
 * Authorization helpers for single-element checks and context access.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class AuthorizationService extends Component implements AuthorizationServiceInterface
{
    /**
     * Returns the authorization context for the current request.
     *
     * @return AuthorizationContext The current authorization context.
     */
    public function getContext(): AuthorizationContext
    {
        return Plugin::getInstance()->getContextFactory()->create();
    }

    /**
     * Whether the given element is accessible in the current context.
     *
     * @param ElementInterface $element Element to evaluate.
     * @param AuthorizationContext|null $context Optional context override.
     *
     * @return bool True when access is allowed.
     */
    public function canAccessElement(ElementInterface $element, ?AuthorizationContext $context = null): bool
    {
        if ($element->id === null) {
            return true;
        }

        return $this->canAccessElementId((int)$element->id, $context);
    }

    /**
     * Whether the given element ID is accessible in the current context.
     *
     * @param int $elementId Element ID to evaluate.
     * @param AuthorizationContext|null $context Optional context override.
     *
     * @return bool True when access is allowed.
     */
    public function canAccessElementId(int $elementId, ?AuthorizationContext $context = null): bool
    {
        $context ??= $this->getContext();

        if ($context->isCpRequest) {
            return true;
        }

        $settings = Plugin::getInstance()->getSettings();
        if (!$settings->authorizationEnabled) {
            return true;
        }

        $policy = Plugin::getInstance()->getPolicies()->getForElementId($elementId);

        return Plugin::getInstance()->getPipeline()->authorize($policy, $context);
    }
}
