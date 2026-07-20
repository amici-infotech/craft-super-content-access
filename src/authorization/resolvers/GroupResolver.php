<?php
namespace amici\SuperContentAccess\authorization\resolvers;

use amici\SuperContentAccess\domain\AuthorizationConstraint;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\contracts\PrincipalResolverInterface;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;

/**
 * Resolves user-group principals against the current user's groups.
 */
class GroupResolver implements PrincipalResolverInterface
{
    public function getType(): string
    {
        return PrincipalType::GROUP;
    }

    public function supports(string $principalType): bool
    {
        return $principalType === PrincipalType::GROUP;
    }

    public function resolve(PolicyPrincipal $principal, AuthorizationContext $context): AuthorizationConstraint
    {
        if ($context->isGuest) {
            return AuthorizationConstraint::deny();
        }

        $groupId = (int)$principal->identifier;
        if ($groupId > 0 && in_array($groupId, $context->groupIds, true)) {
            return AuthorizationConstraint::allow();
        }

        return AuthorizationConstraint::deny();
    }

    public function matchingIdentifiers(AuthorizationContext $context): array
    {
        if ($context->isGuest || $context->groupIds === []) {
            return [];
        }

        return array_map('strval', $context->groupIds);
    }
}
