<?php
/**
 * Resolves user-group principals against the current user's groups.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\authorization\resolvers;

use amici\SuperContentAccess\domain\AuthorizationConstraint;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\contracts\PrincipalResolverInterface;
use amici\SuperContentAccess\domain\PolicyPrincipal;
use amici\SuperContentAccess\domain\PrincipalType;

/**
 * Resolves user-group principals against the current user's groups.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class GroupResolver implements PrincipalResolverInterface
{
    /**
     * Returns the group principal type handle.
     *
     * @return string Principal type handle.
     */
    public function getType(): string
    {
        return PrincipalType::GROUP;
    }

    /**
     * Whether this resolver handles the given principal type.
     *
     * @param string $principalType Principal type handle to test.
     *
     * @return bool True when the type is group.
     */
    public function supports(string $principalType): bool
    {
        return $principalType === PrincipalType::GROUP;
    }

    /**
     * Evaluates a group principal against the current context.
     *
     * @param PolicyPrincipal $principal Group principal to resolve.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return AuthorizationConstraint Allow or deny result.
     */
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

    /**
     * Returns group IDs the current user belongs to.
     *
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return string[] Matching group identifiers.
     */
    public function matchingIdentifiers(AuthorizationContext $context): array
    {
        if ($context->isGuest || $context->groupIds === []) {
            return [];
        }

        return array_map('strval', $context->groupIds);
    }
}
