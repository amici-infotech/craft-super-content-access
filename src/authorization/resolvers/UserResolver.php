<?php
namespace amici\SuperContentAccess\authorization\resolvers;

use amici\SuperContentAccess\domain\AuthorizationConstraint;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\contracts\PrincipalResolverInterface;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;

/**
 * Resolves user principals against the current authenticated user.
 */
class UserResolver implements PrincipalResolverInterface
{
    public function getType(): string
    {
        return PrincipalType::USER;
    }

    public function supports(string $principalType): bool
    {
        return $principalType === PrincipalType::USER;
    }

    public function resolve(PolicyPrincipal $principal, AuthorizationContext $context): AuthorizationConstraint
    {
        if ($context->isGuest || $context->userId === null) {
            return AuthorizationConstraint::deny();
        }

        if ((string)$context->userId === $principal->identifier) {
            return AuthorizationConstraint::allow();
        }

        return AuthorizationConstraint::deny();
    }

    public function matchingIdentifiers(AuthorizationContext $context): array
    {
        if ($context->isGuest || $context->userId === null) {
            return [];
        }

        return [(string)$context->userId];
    }
}
