# 4. Architecture

```text
Application
   │
   ├── Product
   ├── Article
   ├── User
   └── ...
        │
        ▼
      Media
        │
        ├── FILE ─── VichUploader
        │               │
        │               └── LiipImagine
        │
        ├── URL
        ├── EMBED
        └── REMOTE
              │
              └── future Storage Adapter

Media Manager
   │
   ├── Stimulus
   ├── API JSON
   ├── Upload
   ├── Collections
   └── Picker

Messenger
   │
   └── ProcessMediaMessage
```

The bundle core does not depend on `Product`, `Article`, `NeoxCrudBundle`, RabbitMQ or Mercure.

This separation allows the bundle to be used in multiple applications.

[← Configuration](03-configuration.md) · [Media →](05-media-entity.md)
