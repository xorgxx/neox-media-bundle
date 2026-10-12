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

## Options multi-utilisateur (opt-in)

Toutes désactivées par défaut — le comportement global historique est conservé.

```yaml
neox_media:
    ownership:
        enabled: true                    # owner/visibilité/partages — voir doc 27
    folders:
        enabled: true                    # arbre de collections + dossiers dynamiques — voir doc 28
    storage:
        directory:
            enabled: true                # sous-répertoires physiques — voir doc 29
            provider: App\Media\CompanyDirectoryProvider
    security:
        access_role: 'ROLE_ADMIN'        # barrière Media Manager ('PUBLIC_ACCESS' pour désactiver)
```

Détails : [27. Propriété et partages](27-ownership.md) · [28. Dossiers](28-folders.md) · [29. Répertoires physiques](29-storage-dir.md)

[← Installation](02-installation.md) · [Architecture →](04-architecture.md)
