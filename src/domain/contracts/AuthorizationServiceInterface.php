<?php
namespace amici\SuperContentAccess\domain\contracts;

use amici\SuperContentAccess\domain\AuthorizationContext;
use craft\base\ElementInterface;

/**
 * Public contract for authorization evaluation helpers.
 */
interface AuthorizationServiceInterface
{
    public function getContext(): AuthorizationContext;

    public function canAccessElement(ElementInterface $element, ?AuthorizationContext $context = null): bool;

    public function canAccessElementId(int $elementId, ?AuthorizationContext $context = null): bool;
}
