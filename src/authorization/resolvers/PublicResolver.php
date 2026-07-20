<?php
/**
 * Resolves public principals that allow everyone.
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
 * Resolves public principals — always allow.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class PublicResolver implements PrincipalResolverInterface
{
    /**
     * Returns the public principal type handle.
     *
     * @return string Principal type handle.
     */
    public function getType(): string
    {
        return PrincipalType::PUBLIC;
    }

    /**
     * Whether this resolver handles the given principal type.
     *
     * @param string $principalType Principal type handle to test.
     *
     * @return bool True when the type is public.
     */
    public function supports(string $principalType): bool
    {
        return $principalType === PrincipalType::PUBLIC;
    }

    /**
     * Evaluates a public principal against the current context.
     *
     * @param PolicyPrincipal $principal Public principal to resolve.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return AuthorizationConstraint Always allows access.
     */
    public function resolve(PolicyPrincipal $principal, AuthorizationContext $context): AuthorizationConstraint
    {
        return AuthorizationConstraint::allow();
    }

    /**
     * Returns a wildcard identifier for query matching.
     *
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return string[] Matching public identifiers.
     */
    public function matchingIdentifiers(AuthorizationContext $context): array
    {
        return [PrincipalType::WILDCARD];
    }
}
