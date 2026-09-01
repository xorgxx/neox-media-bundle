<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Metadata;

final readonly class MediaFieldDefinition
{
    /**
     * @param array<int, string> $accept
     */
    public function __construct(
        public string $property,
        public ?string $role,
        public array $accept,
        public bool $multiple,
        public bool $sortable,
    ) {
    }
}
