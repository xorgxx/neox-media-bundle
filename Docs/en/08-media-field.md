# 8. MediaField Attribute

`#[MediaField]` describes the expected usage of the media.

```php
#[MediaField(
    role: 'cover',
    accept: ['image/*'],
    multiple: false,
    sortable: false
)]
```

Options:

| Option | Description |
|---|---|
| `role` | logical role of the media |
| `accept` | MIME types allowed by the UI |
| `multiple` | allows multiple media |
| `sortable` | enables manual ordering |

The attribute does not replace Doctrine mapping.

Doctrine describes the relation. `MediaField` describes the media behavior.

`MediaMetadataReader` can automatically analyze properties bearing this attribute.

[← Business entities](07-entity-integration.md) · [MediaPicker →](09-media-picker.md)
