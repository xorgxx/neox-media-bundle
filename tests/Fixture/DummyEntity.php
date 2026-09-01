<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Fixture;

use Neox\MediaBundle\Attribute\MediaField;

class DummyEntity
{
    #[MediaField(role: 'cover', accept: ['image/*'])]
    private ?object $cover = null;

    #[MediaField(role: 'gallery', accept: ['image/*', 'video/*'], multiple: true, sortable: true)]
    private ?array $gallery = null;

    private ?string $name = null;
}
