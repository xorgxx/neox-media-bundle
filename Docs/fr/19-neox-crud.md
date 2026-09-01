# 19. Intégration NeoxCrudBundle

L'intégration est volontairement optionnelle.

```text
NeoxCrudBundle
       │
       ▼
MediaCrudFieldGuesser
       │
       ▼
MediaMetadataReader
       │
       ▼
#[MediaField]
```

`NeoxMediaBundle` ne dépend pas de `NeoxCrudBundle`.

Le bridge lit les propriétés média et retourne les informations nécessaires au générateur de formulaire CRUD.

Exemple :

```php
#[MediaField(
    role: 'cover',
    accept: ['image/*']
)]
private ?Media $cover;
```

Le CRUD peut automatiquement déduire :

```text
type       = MediaPickerType
multiple   = false
accept     = image/*
sortable   = false
```

Cela permet au même bundle média de fonctionner avec ou sans NeoxCrudBundle.

[← RabbitMQ](18-rabbitmq.md) · [Sécurité →](20-security.md)
