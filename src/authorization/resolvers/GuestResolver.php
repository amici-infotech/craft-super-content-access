<?php
/**
 * Resolves guest principals for unauthenticated visitors.
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
 * Resolves guest principals for unauthenticated visitors.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class GuestResolver implements PrincipalResolverInterface
{
    /**
     * Returns the guest principal type handle.
     *
     * @return string Principal type handle.
     */
    public function getType(): string
    {
        return PrincipalType::GUEST;
    }

    /**
     * Whether this resolver handles the given principal type.
     *
     * @param string $principalType Principal type handle to test.
     *
     * @return bool True when the type is guest.
     */
    public function supports(string $principalType): bool
    {
        return $principalType === PrincipalType::GUEST;
    }

    /**
     * Evaluates a guest principal against the current context.
     *
     * @param PolicyPrincipal $principal Guest principal to resolve.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return AuthorizationConstraint Allow or deny result.
     */
    public function resolve(PolicyPrincipal $principal, AuthorizationContext $context): AuthorizationConstraint
    {
        return $context->isGuest
            ? AuthorizationConstraint::allow()
            : AuthorizationConstraint::deny();
    }

    /**
     * Returns a wildcard identifier when the visitor is a guest.
     *
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return string[] Matching guest identifiers.
     */
    public function matchingIdentifiers(AuthorizationContext $context): array
    {
        return $context->isGuest ? [PrincipalType::WILDCARD] : [];
    }
}
