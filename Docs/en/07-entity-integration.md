# 7. Integration in a Business Entity

## Single Media

```php
use Neox\MediaBundle\Attribute\MediaField;
use Neox\MediaBundle\Entity\Media;

#[ORM\ManyToOne(targetEntity: Media::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
#[MediaField(role: 'cover', accept: ['image/*'])]
private ?Media $cover = null;
```

The `Product` entity contains no `file`, `path` or `url`.

## Multiple Media

For a simple gallery:

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

For a relation requiring more business data, create an intermediate entity in the application, e.g. `ProductMedia`.

It can carry:

- role;
- position;
- specific caption;
- product-specific options.

[← Sources](06-media-sources.md) · [MediaField →](08-media-field.md)
