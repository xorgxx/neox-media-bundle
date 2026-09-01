# 22. Complete Examples

## Product with cover

```php
#[ORM\Entity]
class Product
{
    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[MediaField(role: 'cover', accept: ['image/*'])]
    private ?Media $cover = null;
}
```

## Form

```php
$builder->add('cover', MediaPickerType::class, [
    'accept' => ['image/*'],
]);
```

## Twig

```twig
{% if product.cover %}
    {{ neox_media_render(product.cover) }}
{% endif %}
```

## Gallery

```php
#[ORM\ManyToMany(targetEntity: Media::class)]
#[MediaField(
    role: 'gallery',
    accept: ['image/*', 'video/*'],
    multiple: true,
    sortable: true
)]
private Collection $gallery;
```

## Document

```php
#[ORM\ManyToOne(targetEntity: Media::class)]
#[MediaField(
    role: 'documentation',
    accept: ['application/pdf']
)]
private ?Media $documentation = null;
```

The business entity remains independent of the storage mode.

[← Extension](21-extension.md) · [API →](23-api-reference.md)
