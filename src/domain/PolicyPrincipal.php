<?php
namespace amici\SuperContentAccess\domain;

/**
 * A single authorization target within an Access Policy.
 */
final class PolicyPrincipal
{
    public function __construct(
        public readonly string $type,
        public readonly string $identifier,
        public readonly ?int $id = null,
    ) {
    }

    /**
     * @param array{type: string, identifier: string, id?: int|null} $data
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
     * @return array{type: string, identifier: string, id: int|null}
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
