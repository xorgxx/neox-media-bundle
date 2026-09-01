# 25. Storage Adapters

## Purpose

The `REMOTE` source covers files stored outside the local filesystem: S3, Cloudflare R2, MinIO, Scaleway Object Storage, NAS, CDN.

The bundle **does not depend on any** cloud service. It provides:

- a `StorageInterface` interface;
- a default `FilesystemStorageAdapter` (CDN or local);
- a single injection point in `MediaUrlGenerator`.

## Interface

```php
namespace Neox\MediaBundle\Storage;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('neox_media.storage_adapter')]
interface StorageInterface
{
    public function resolveUrl(string $storageKey): ?string;
    public function exists(string $storageKey): bool;
}
```

The `#[AutoconfigureTag]` attribute automatically tags any implementation with `neox_media.storage_adapter`.

## Default Adapter: FilesystemStorageAdapter

Provided by the bundle. Builds a URL from a CDN prefix or local path.

```yaml
# config/packages/neox_media.yaml
neox_media:
    storage:
        adapter: filesystem
        cdn_prefix: 'https://cdn.example.com/media'
```

- empty `cdn_prefix` → local URL `/uploads/media/{storageKey}`
- set `cdn_prefix` → CDN URL `https://cdn.example.com/media/{storageKey}`

## Creating an S3 Adapter

### 1. Implement the interface in the app

```php
// src/Storage/S3StorageAdapter.php
namespace App\Storage;

use Neox\MediaBundle\Storage\StorageInterface;
use Aws\S3\S3Client;

final class S3StorageAdapter implements StorageInterface
{
    public function __construct(
        private readonly S3Client $s3,
        private readonly string $bucket,
        private readonly string $cdnUrl = '',
    ) {}

    public function resolveUrl(string $storageKey): ?string
    {
        if ($this->cdnUrl !== '') {
            return rtrim($this->cdnUrl, '/') . '/' . ltrim($storageKey, '/');
        }

        return $this->s3->getObjectUrl($this->bucket, $storageKey);
    }

    public function exists(string $storageKey): bool
    {
        return $this->s3->doesObjectExist($this->bucket, $storageKey);
    }
}
```

### 2. Register the service

```yaml
# config/services.yaml
services:
    App\Storage\S3StorageAdapter:
        arguments:
            $s3: '@aws.s3_client'
            $bucket: '%env(S3_BUCKET)%'
            $cdnUrl: '%env(CDN_URL)%'
        # The tag is auto-applied via #[AutoconfigureTag]
```

### 3. Disable the bundle's default adapter

```yaml
# config/packages/neox_media.yaml
neox_media:
    storage:
        adapter: null
```

Then manually bind `MediaUrlGenerator` to your adapter:

```yaml
# config/services.yaml
services:
    Neox\MediaBundle\Service\MediaUrlGenerator:
        arguments:
            $storage: '@App\Storage\S3StorageAdapter'
```

## Creating a REMOTE Media

```php
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;

$media = new Media();
$media->setSource(MediaSource::REMOTE)
      ->setStorageKey('uploads/products/cover-2024.jpg')
      ->setType(MediaType::IMAGE)
      ->setTitle('Cover 2024');

$em->persist($media);
$em->flush();
```

## Getting the URL

### PHP

```php
$url = $generator->url($media);    // Raw URL
$thumb = $generator->filter($media, 'neox_media_thumb');  // LiipImagine URL
```

### Twig

```twig
<img src="{{ neox_media_url(product.cover) }}">
<img src="{{ neox_media_filter(product.cover, 'neox_media_thumb') }}">
```

## Flow

```text
Media.source = REMOTE
  → MediaUrlGenerator::url()
      → StorageInterface::resolveUrl(storageKey)
          → FilesystemStorageAdapter   (CDN prefix)
          → S3StorageAdapter           (AWS S3)
          → R2StorageAdapter           (Cloudflare R2)
          → MinioStorageAdapter        (self-hosted)
      → fallback: Media.url if no adapter
```

## Rules

- The bundle never knows about S3, R2, MinIO or Flysystem.
- No cloud dependency in `composer.json`.
- The interface is auto-tagged: no manual config needed.
- The default adapter (`FilesystemStorageAdapter`) is active as long as `adapter` is not `null`.

[← API reference](23-api-reference.md) · [Troubleshooting →](24-troubleshooting.md)
