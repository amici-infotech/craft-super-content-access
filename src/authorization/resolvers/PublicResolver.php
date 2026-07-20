<?php
namespace amici\SuperContentAccess\authorization\resolvers;

use amici\SuperContentAccess\domain\AuthorizationConstraint;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\contracts\PrincipalResolverInterface;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;

/**
 * Resolves public principals — always allow.
 */
class PublicResolver implements PrincipalResolverInterface
{
    public function getType(): string
    {
        return PrincipalType::PUBLIC;
    }

    public function supports(string $principalType): bool
    {
        return $principalType === PrincipalType::PUBLIC;
    }

    public function resolve(PolicyPrincipal $principal, AuthorizationContext $context): AuthorizationConstraint
    {
        return AuthorizationConstraint::allow();
    }

    public function matchingIdentifiers(AuthorizationContext $context): array
    {
        return [PrincipalType::WILDCARD];
    }
}
