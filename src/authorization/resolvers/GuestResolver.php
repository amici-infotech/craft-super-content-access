<?php
namespace amici\SuperContentAccess\authorization\resolvers;

use amici\SuperContentAccess\domain\AuthorizationConstraint;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\contracts\PrincipalResolverInterface;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;

/**
 * Resolves guest principals for unauthenticated visitors.
 */
class GuestResolver implements PrincipalResolverInterface
{
    public function getType(): string
    {
        return PrincipalType::GUEST;
    }

    public function supports(string $principalType): bool
    {
        return $principalType === PrincipalType::GUEST;
    }

    public function resolve(PolicyPrincipal $principal, AuthorizationContext $context): AuthorizationConstraint
    {
        return $context->isGuest
            ? AuthorizationConstraint::allow()
            : AuthorizationConstraint::deny();
    }

    public function matchingIdentifiers(AuthorizationContext $context): array
    {
        return $context->isGuest ? [PrincipalType::WILDCARD] : [];
    }
}
