# 19. NeoxCrudBundle Integration

The integration is intentionally optional.

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

`NeoxMediaBundle` does not depend on `NeoxCrudBundle`.

The bridge reads media properties and returns the information needed by the CRUD form generator.

Example:

```php
#[MediaField(
    role: 'cover',
    accept: ['image/*']
)]
private ?Media $cover;
```

The CRUD can automatically infer:

```text
type       = MediaPickerType
multiple   = false
accept     = image/*
sortable   = false
```

This allows the same media bundle to work with or without NeoxCrudBundle.

[← RabbitMQ](18-rabbitmq.md) · [Security →](20-security.md)
