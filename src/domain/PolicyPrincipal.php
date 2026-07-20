<?php
/**
 * Value object representing one authorization target within an Access Policy.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\domain;

/**
 * A single authorization target within an Access Policy.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
final class PolicyPrincipal
{
    /**
     * Creates a policy principal value object.
     *
     * @param string $type Principal type handle (user, group, guest, public).
     * @param string $identifier Type-specific identifier (user ID, group ID, or wildcard).
     * @param int|null $id Database record ID when loaded from storage.
     */
    public function __construct(
        public readonly string $type,
        public readonly string $identifier,
        public readonly ?int $id = null,
    ) {
    }

    /**
     * Builds a principal from a serialized array.
     *
     * @param array{type: string, identifier: string, id?: int|null} $data Serialized principal data.
     *
     * @return self The constructed principal.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string)$data['type'],
            (string)$data['identifier'],
            isset($data['id']) ? (int)$data['id'] : null,
        );
    }

    /**
     * Serializes the principal to an array.
     *
     * @return array{type: string, identifier: string, id: int|null} Serialized principal data.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'identifier' => $this->identifier,
            'id' => $this->id,
        ];
    }
}
