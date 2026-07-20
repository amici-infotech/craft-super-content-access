<?php
/**
 * Result of resolving a single policy principal against a context.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\domain;

/**
 * Result of resolving a single Policy Principal against a context.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
final class AuthorizationConstraint
{
    /**
     * Creates an authorization constraint value object.
     *
     * @param bool $allowed Whether access is allowed for this principal match.
     */
    private function __construct(
        public readonly bool $allowed,
    ) {
    }

    /**
     * Returns a constraint that allows access.
     *
     * @return self An allow constraint.
     */
    public static function allow(): self
    {
        return new self(true);
    }

    /**
     * Returns a constraint that denies access.
     *
     * @return self A deny constraint.
     */
    public static function deny(): self
    {
        return new self(false);
    }
}
