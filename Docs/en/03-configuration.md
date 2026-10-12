# 3. Configuration

The bundle separates business configuration from storage.

## VichUploader

Example:

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

## Upload Sandbox

The upload sandbox marks allowed-but-risky files as not publicly publishable until review.

```yaml
neox_media:
    upload:
        sandbox:
            enabled: true
            mime: ['image/svg+xml', 'application/pdf', 'application/zip']
            extensions: ['svg', 'pdf', 'zip']
```

When disabled, allowed SVG/PDF/ZIP files follow the normal upload flow immediately.

## Messenger

The bundle uses Messenger and does not impose RabbitMQ.

The host project chooses its transport.

## Multi-user options (opt-in)

All disabled by default — legacy global behaviour is preserved.

```yaml
neox_media:
    ownership:
        enabled: true                    # owner/visibility/shares — see docs 27
    folders:
        enabled: true                    # collection tree + smart folders — see docs 28
    storage:
        directory:
            enabled: true                # physical subdirs — see docs 29
            provider: App\Media\CompanyDirectoryProvider
    security:
        access_role: 'ROLE_ADMIN'        # Media Manager gate ('PUBLIC_ACCESS' to disable)
```

Details: [27. Ownership](27-ownership.md) · [28. Folders](28-folders.md) · [29. Physical directories](29-storage-dir.md)

[← Installation](02-installation.md) · [Architecture →](04-architecture.md)
