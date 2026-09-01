# 22. Exemples complets

## Product avec cover

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

## Formulaire

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

## Galerie

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

L'entité métier reste indépendante du mode de stockage.

[← Extension](21-extension.md) · [API →](23-api-reference.md)
