<?php
namespace amici\SuperContentAccess\domain;

/**
 * Built-in principal type identifiers for v1.
 */
final class PrincipalType
{
    public const USER = 'user';
    public const GROUP = 'group';
    public const GUEST = 'guest';
    public const PUBLIC = 'public';

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

    private function __construct()
    {
    }
}
