# 6. Sources de médias

`MediaSource` décrit l'origine technique du média.

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

Fichier importé et géré par VichUploader.

## URL

Ressource accessible directement par une URL externe.

## EMBED

Ressource destinée à être intégrée via un fournisseur externe.

Exemples : vidéo ou lecteur distant.

## REMOTE

Fichier contrôlé par l'application mais stocké hors du filesystem local.

Exemples :

- S3 ;
- Cloudflare R2 ;
- MinIO ;
- Scaleway Object Storage ;
- NAS.

L'application appelle toujours le générateur d'URL du bundle au lieu de reconstruire le chemin.

### StorageInterface

La résolution d'URL pour la source REMOTE passe par `StorageInterface`.
Le bundle fournit `FilesystemStorageAdapter` par défaut (CDN ou local).
Pour S3/R2/MinIO, implémenter `StorageInterface` dans l'app.

Voir [Storage adapters](25-storage.md) pour la configuration et les exemples.

[← Media](05-media-entity.md) · [Entités métier →](07-entity-integration.md)
