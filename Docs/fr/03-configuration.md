# 3. Configuration

Le bundle sépare la configuration métier du stockage.

## VichUploader

Exemple :

```yaml
vich_uploader:
    db_driver: orm

    mappings:
        neox_media:
            uri_prefix: /uploads/media
            upload_destination: '%kernel.project_dir%/public/uploads/media'
            delete_on_remove: true
            delete_on_update: true
```

## LiipImagine

```yaml
liip_imagine:
    filter_sets:
        neox_media_thumb:
            quality: 85
            filters:
                thumbnail:
                    size: [200, 200]
                    mode: outbound

        neox_media_medium:
            quality: 85
            filters:
                thumbnail:
                    size: [1024, 1024]
                    mode: inset
```

## Messenger

Le bundle utilise Messenger et n'impose pas RabbitMQ.

Le projet hôte choisit son transport.

[← Installation](02-installation.md) · [Architecture →](04-architecture.md)
