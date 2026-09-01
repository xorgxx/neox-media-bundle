# 8. Attribut MediaField

`#[MediaField]` décrit l'utilisation attendue du média.

```php
#[MediaField(
    role: 'cover',
    accept: ['image/*'],
    multiple: false,
    sortable: false
)]
```

Options :

| Option | Description |
|---|---|
| `role` | rôle logique du média |
| `accept` | types MIME autorisés par l'interface |
| `multiple` | autorise plusieurs médias |
| `sortable` | permet un ordre manuel |

L'attribut ne remplace pas le mapping Doctrine.

Doctrine décrit la relation. `MediaField` décrit le comportement média.

`MediaMetadataReader` peut analyser automatiquement les propriétés portant cet attribut.

[← Entités métier](07-entity-integration.md) · [MediaPicker →](09-media-picker.md)
