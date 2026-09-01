<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class MediaField
{
    /**
     * @param array<int, string> $accept
     */
    public function __construct(
        public readonly ?string $role = null,
        public readonly array $accept = [],
        public readonly bool $multiple = false,
        public readonly bool $sortable = false,
    ) {
    }
}
