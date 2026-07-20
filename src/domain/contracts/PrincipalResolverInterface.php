<?php
/**
 * Contract for principal-type resolvers used during authorization.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\domain\contracts;

use amici\SuperContentAccess\domain\AuthorizationConstraint;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\PolicyPrincipal;

/**
 * Contract for principal-type resolvers.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
interface PrincipalResolverInterface
{
    /**
     * Returns the principal type handle this resolver owns (e.g. user, group).
     *
     * @return string Principal type handle.
     */
    public function getType(): string;

    /**
     * Whether this resolver handles the given principal type.
     *
     * @param string $principalType Principal type handle to test.
     *
     * @return bool True when this resolver supports the type.
     */
    public function supports(string $principalType): bool;

    /**
     * Evaluates one principal against the current context.
     *
     * @param PolicyPrincipal $principal Principal to resolve.
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return AuthorizationConstraint Allow or deny result.
     */
    public function resolve(
        PolicyPrincipal $principal,
        AuthorizationContext $context
    ): AuthorizationConstraint;

    /**
     * Returns identifiers of this type that match the context for query EXISTS clauses.
     *
     * Empty array means this type contributes no match for the current context.
     * Guest/public use ['*'] when applicable.
     *
     * @param AuthorizationContext $context Current authorization context.
     *
     * @return string[] Matching principal identifiers.
     */
    public function matchingIdentifiers(AuthorizationContext $context): array;
}
