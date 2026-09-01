# 6. Media Sources

`MediaSource` describes the technical origin of the media.

```php
enum MediaSource: string
{
    case FILE = 'file';
    case URL = 'url';
    case EMBED = 'embed';
    case REMOTE = 'remote';
}
```

## FILE

File imported and managed by VichUploader.

## URL

Resource accessible directly via an external URL.

## EMBED

Resource intended to be embedded via an external provider.

Examples: video or remote player.

## REMOTE

File controlled by the application but stored outside the local filesystem.

Examples:

- S3;
- Cloudflare R2;
- MinIO;
- Scaleway Object Storage;
- NAS.

The application always calls the bundle's URL generator instead of reconstructing the path.

### StorageInterface

URL resolution for the REMOTE source goes through `StorageInterface`.
The bundle provides `FilesystemStorageAdapter` by default (CDN or local).
For S3/R2/MinIO, implement `StorageInterface` in the app.

See [Storage adapters](25-storage.md) for configuration and examples.

[← Media](05-media-entity.md) · [Business entities →](07-entity-integration.md)
