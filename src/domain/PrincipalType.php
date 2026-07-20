<?php
/**
 * Built-in principal type identifiers for access policies.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\domain;

/**
 * Built-in principal type identifiers for v1.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
final class PrincipalType
{
    /**
     * Principal type for a specific Craft user.
     */
    public const USER = 'user';

    /**
     * Principal type for a Craft user group.
     */
    public const GROUP = 'group';

    /**
     * Principal type for unauthenticated visitors.
     */
    public const GUEST = 'guest';

    /**
     * Principal type that allows everyone.
     */
    public const PUBLIC = 'public';

    /**
     * All supported principal type handles.
     */
    public const ALL = [
        self::USER,
        self::GROUP,
        self::GUEST,
        self::PUBLIC,
    ];

    /**
     * Wildcard identifier used by guest/public principals.
     */
    public const WILDCARD = '*';

    /**
     * Prevents instantiation of this constants-only class.
     */
    private function __construct()
    {
    }
}
