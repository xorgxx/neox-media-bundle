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
              └── futur Storage Adapter

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

Le cœur du bundle ne dépend pas de `Product`, `Article`, `NeoxCrudBundle`, RabbitMQ ou Mercure.

Cette séparation permet d'utiliser le bundle dans plusieurs applications.

[← Configuration](03-configuration.md) · [Media →](05-media-entity.md)
