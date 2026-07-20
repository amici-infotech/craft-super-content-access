<?php
namespace amici\SuperContentAccess\domain\contracts;

use amici\SuperContentAccess\domain\AccessPolicy;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use craft\base\ElementInterface;

/**
 * Public contract for Access Policy management.
 */
interface PolicyServiceInterface
{
    public function getForElement(ElementInterface $element): ?AccessPolicy;

    public function getForElementId(int $elementId): ?AccessPolicy;

    /**
     * @param PolicyPrincipal[] $principals
     */
    public function saveForElement(int $elementId, array $principals): AccessPolicy;

    public function deleteForElement(int $elementId): bool;
}
