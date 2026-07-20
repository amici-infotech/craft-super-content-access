<?php
/**
 * Domain aggregate describing who may access a protected element.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\domain;

/**
 * Aggregate root describing who may access a protected element.
 *
 * Does not evaluate authorization.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
final class AccessPolicy
{
    /**
     * Creates an access policy value object.
     *
     * @param int $elementId Element ID the policy protects.
     * @param PolicyPrincipal[] $principals Allowed principals.
     * @param int|null $id Database record ID when loaded from storage.
     * @param string|null $uid Craft UID when loaded from storage.
     */
    public function __construct(
        public readonly int $elementId,
        public readonly array $principals = [],
        public readonly ?int $id = null,
        public readonly ?string $uid = null,
    ) {
    }

    /**
     * Whether the policy defines at least one principal.
     *
     * @return bool True when principals are present.
     */
    public function hasPrincipals(): bool
    {
        return $this->principals !== [];
    }

    /**
     * Whether the policy includes a principal of the given type.
     *
     * @param string $type Principal type handle to look for.
     *
     * @return bool True when a matching principal exists.
     */
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
     * Serializes the policy to an array.
     *
     * @return array{id: int|null, elementId: int, uid: string|null, principals: list<array{type: string, identifier: string, id: int|null}>} Serialized policy data.
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
