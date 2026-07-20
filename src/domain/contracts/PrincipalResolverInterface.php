<?php
namespace amici\SuperContentAccess\domain\contracts;

use amici\SuperContentAccess\domain\AuthorizationConstraint;
use amici\SuperContentAccess\domain\AuthorizationContext;
use amici\SuperContentAccess\domain\PolicyPrincipal;

/**
 * Contract for principal-type resolvers.
 */
interface PrincipalResolverInterface
{
    /**
     * Principal type handle this resolver owns (e.g. user, group).
     */
    public function getType(): string;

    public function supports(string $principalType): bool;

    /**
     * Evaluate one principal against the current context.
     */
    public function resolve(
        PolicyPrincipal $principal,
        AuthorizationContext $context
    ): AuthorizationConstraint;

    /**
     * Identifiers of this type that match the context, used for query EXISTS clauses.
     *
     * Empty array means this type contributes no match for the current context.
     * Guest/public use ['*'] when applicable.
     *
     * @return string[]
     */
    public function matchingIdentifiers(AuthorizationContext $context): array;
}
