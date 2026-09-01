# 25. Storage adapters

## Objectif

La source `REMOTE` couvre les fichiers stockés hors du filesystem local : S3, Cloudflare R2, MinIO, Scaleway Object Storage, NAS, CDN.

Le bundle **ne dépend d'aucun** service cloud. Il fournit :

- une interface `StorageInterface` ;
- un adapter par défaut `FilesystemStorageAdapter` (CDN ou local) ;
- un point d'injection unique dans `MediaUrlGenerator`.

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

L'attribut `#[AutoconfigureTag]` tag automatiquement toute implémentation avec `neox_media.storage_adapter`.

## Adapter par défaut : FilesystemStorageAdapter

Fourni par le bundle. Construit une URL à partir d'un préfixe CDN ou du chemin local.

```yaml
# config/packages/neox_media.yaml
neox_media:
    storage:
        adapter: filesystem
        cdn_prefix: 'https://cdn.example.com/media'
```

- `cdn_prefix` vide → URL locale `/uploads/media/{storageKey}`
- `cdn_prefix` défini → URL CDN `https://cdn.example.com/media/{storageKey}`

## Créer un adapter S3

### 1. Implémenter l'interface dans l'app

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

### 2. Enregistrer le service

```yaml
# config/services.yaml
services:
    App\Storage\S3StorageAdapter:
        arguments:
            $s3: '@aws.s3_client'
            $bucket: '%env(S3_BUCKET)%'
            $cdnUrl: '%env(CDN_URL)%'
        # Le tag est auto-appliqué via #[AutoconfigureTag]
```

### 3. Désactiver l'adapter par défaut du bundle

```yaml
# config/packages/neox_media.yaml
neox_media:
    storage:
        adapter: null
```

Puis lier manuellement `MediaUrlGenerator` vers votre adapter :

```yaml
# config/services.yaml
services:
    Neox\MediaBundle\Service\MediaUrlGenerator:
        arguments:
            $storage: '@App\Storage\S3StorageAdapter'
```

## Créer un média REMOTE

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

## Récupérer l'URL

### PHP

```php
$url = $generator->url($media);    // URL brute
$thumb = $generator->filter($media, 'neox_media_thumb');  // URL LiipImagine
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
      → fallback: Media.url si pas d'adapter
```

## Règles

- Le bundle ne connaît jamais S3, R2, MinIO ou Flysystem.
- Aucune dépendance cloud dans `composer.json`.
- L'interface est auto-taggée : aucune config manuelle nécessaire.
- L'adapter par défaut (`FilesystemStorageAdapter`) est activé tant que `adapter` n'est pas `null`.

[← API reference](23-api-reference.md) · [Troubleshooting →](24-troubleshooting.md)
