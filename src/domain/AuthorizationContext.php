<?php
namespace amici\SuperContentAccess\domain;

/**
 * Immutable, request-scoped authorization snapshot.
 */
final class AuthorizationContext
{
    /**
     * @param int[] $groupIds
     * @param array<string, mixed> $metadata
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

    public function isAuthenticated(): bool
    {
        return !$this->isGuest && $this->userId !== null;
    }
}
