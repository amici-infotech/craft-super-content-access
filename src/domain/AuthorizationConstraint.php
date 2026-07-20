<?php
namespace amici\SuperContentAccess\domain;

/**
 * Result of resolving a single Policy Principal against a context.
 */
final class AuthorizationConstraint
{
    private function __construct(
        public readonly bool $allowed,
    ) {
    }

    public static function allow(): self
    {
        return new self(true);
    }

    public static function deny(): self
    {
        return new self(false);
    }
}
