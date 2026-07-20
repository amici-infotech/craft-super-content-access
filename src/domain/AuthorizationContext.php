<?php
/**
 * Immutable request-scoped snapshot used during authorization evaluation.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\domain;

/**
 * Immutable, request-scoped authorization snapshot.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
final class AuthorizationContext
{
    /**
     * Creates an authorization context for the current request.
     *
     * @param int|null $userId Authenticated user ID, or null for guests.
     * @param int[] $groupIds Group IDs the user belongs to.
     * @param bool $isGuest Whether the visitor is unauthenticated.
     * @param int|null $siteId Current site ID when available.
     * @param bool $isCpRequest Whether the request targets the Control Panel.
     * @param array<string, mixed> $metadata Optional diagnostic metadata.
     */
    public function __construct(
        public readonly ?int $userId,
        public readonly array $groupIds,
        public readonly bool $isGuest,
        public readonly ?int $siteId,
        public readonly bool $isCpRequest,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * Whether the context represents a logged-in user.
     *
     * @return bool True when the visitor is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return !$this->isGuest && $this->userId !== null;
    }
}
