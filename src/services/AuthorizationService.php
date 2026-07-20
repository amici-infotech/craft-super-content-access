<?php
namespace amici\SuperContentAccess\services;

use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\contracts\AuthorizationServiceInterface;
use amici\SuperContentAccess\Plugin;
use craft\base\Component;
use craft\base\ElementInterface;

/**
 * Authorization helpers for single-element checks and context access.
 */
class AuthorizationService extends Component implements AuthorizationServiceInterface
{
    public function getContext(): AuthorizationContext
    {
        return Plugin::getInstance()->getContextFactory()->create();
    }

    public function canAccessElement(ElementInterface $element, ?AuthorizationContext $context = null): bool
    {
        if ($element->id === null) {
            return true;
        }

        return $this->canAccessElementId((int)$element->id, $context);
    }

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
