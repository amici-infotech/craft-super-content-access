<?php
namespace amici\SuperContentAccess\domain;

/**
 * Aggregate root describing who may access a protected element.
 *
 * Does not evaluate authorization.
 */
final class AccessPolicy
{
    /**
     * @param PolicyPrincipal[] $principals
     */
    public function __construct(
        public readonly int $elementId,
        public readonly array $principals = [],
        public readonly ?int $id = null,
        public readonly ?string $uid = null,
    ) {
    }

    public function hasPrincipals(): bool
    {
        return $this->principals !== [];
    }

    public function hasPrincipalType(string $type): bool
    {
        foreach ($this->principals as $principal) {
            if ($principal->type === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{id: int|null, elementId: int, uid: string|null, principals: list<array{type: string, identifier: string, id: int|null}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'elementId' => $this->elementId,
            'uid' => $this->uid,
            'principals' => array_map(
                static fn(PolicyPrincipal $principal): array => $principal->toArray(),
                $this->principals
            ),
        ];
    }
}
