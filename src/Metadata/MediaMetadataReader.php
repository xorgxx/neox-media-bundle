<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Metadata;

use Neox\MediaBundle\Attribute\MediaField;

final class MediaMetadataReader
{
    /**
     * @param class-string|object $class
     *
     * @return MediaFieldDefinition[]
     */
    public function read(string|object $class): array
    {
        $reflection = new \ReflectionClass($class);
        $result = [];

        foreach ($reflection->getProperties() as $property) {
            $attribute = $property->getAttributes(MediaField::class)[0] ?? null;
            if (!$attribute) {
                continue;
            }

            /** @var MediaField $config */
            $config = $attribute->newInstance();
            $result[] = new MediaFieldDefinition(
                $property->getName(),
                $config->role,
                $config->accept,
                $config->multiple,
                $config->sortable,
            );
        }

        return $result;
    }
}
