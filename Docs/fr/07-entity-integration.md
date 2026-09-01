# 7. Intégration dans une entité métier

## Média unique

```php
use Neox\MediaBundle\Attribute\MediaField;
use Neox\MediaBundle\Entity\Media;

#[ORM\ManyToOne(targetEntity: Media::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
#[MediaField(role: 'cover', accept: ['image/*'])]
private ?Media $cover = null;
```

L'entité `Product` ne contient aucun `file`, `path` ou `url`.

## Plusieurs médias

Pour une galerie simple :

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

Pour une relation nécessitant davantage de données métier, créer une entité intermédiaire dans l'application, par exemple `ProductMedia`.

Elle peut porter :

- rôle ;
- position ;
- légende spécifique ;
- options propres au produit.

[← Sources](06-media-sources.md) · [MediaField →](08-media-field.md)
