<?php
/**
 * Resolves user principals against the current authenticated user.
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
 * Resolves user principals against the current authenticated user.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class UserResolver implements PrincipalResolverInterface
{
    /**
     * Returns the user principal type handle.
     *
     * @return string Principal type handle.
     */
    public function getType(): string
    {
        return PrincipalType::USER;
    }

    /**
     * Whether this resolver handles the given principal type.
     *
     * @param string $principalType Principal type handle to test.
     *
     * @return bool True when the type is user.
     */
    public function supports(string $principalType): bool
    {
        return $principalType === PrincipalType::USER;
    }

    /**
     * Evaluates a user principal against the current context.
     *
     * @param PolicyPrincipal $principal User principal to resolve.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return AuthorizationConstraint Allow or deny result.
     */
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

    /**
     * Returns the current user's ID when authenticated.
     *
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return string[] Matching user identifiers.
     */
    public function matchingIdentifiers(AuthorizationContext $context): array
    {
        if ($context->isGuest || $context->userId === null) {
            return [];
        }

        return [(string)$context->userId];
    }
}
