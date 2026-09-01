<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Bridge\Crud;

use Neox\MediaBundle\Form\MediaPickerType;
use Neox\MediaBundle\Metadata\MediaMetadataReader;

/**
 * Optional bridge consumed by a CRUD system.
 * NeoxMediaBundle never requires NeoxCrudBundle.
 */
final readonly class MediaCrudFieldGuesser
{
    public function __construct(private MediaMetadataReader $metadata)
    {
    }

    /**
     * @param class-string|object $entity
     *
     * @return array<string, array{type: string, options: array{multiple: bool, accept: array<int, string>, sortable: bool}}>
     */
    public function guess(string|object $entity): array
    {
        $fields = [];

        foreach ($this->metadata->read($entity) as $definition) {
            $fields[$definition->property] = [
                'type' => MediaPickerType::class,
                'options' => [
                    'multiple' => $definition->multiple,
                    'accept' => $definition->accept,
                    'sortable' => $definition->sortable,
                ],
            ];
        }

        return $fields;
    }
}
