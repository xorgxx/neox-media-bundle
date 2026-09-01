# 26. Embed Providers

## Objectif

La source `EMBED` couvre les médias intégrés via un fournisseur externe : YouTube, Vimeo, Dailymotion, SoundCloud, etc.

Le bundle fournit :

- une interface `EmbedProviderInterface` ;
- 3 providers prêts à l'emploi : YouTube, Vimeo, Dailymotion ;
- un `EmbedProviderRegistry` qui chaîne les providers ;
- un endpoint API pour créer un média EMBED depuis une URL.

## Interface

```php
namespace Neox\MediaBundle\Embed;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('neox_media.embed_provider')]
interface EmbedProviderInterface
{
    public function supports(string $url): bool;
    public function resolve(string $url): ?Media;
    public function getName(): string;
}
```

L'attribut `#[AutoconfigureTag]` tag automatiquement toute implémentation avec `neox_media.embed_provider`.

## Providers fournis

### YoutubeProvider

URLs supportées :

- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://www.youtube.com/embed/VIDEO_ID`
- `https://www.youtube.com/shorts/VIDEO_ID`

Extrait l'ID vidéo, génère l'URL d'embed `https://www.youtube.com/embed/VIDEO_ID`, et stocke le thumbnail dans `metadata['thumbnail']`.

### VimeoProvider

URLs supportées :

- `https://vimeo.com/VIDEO_ID`
- `https://player.vimeo.com/video/VIDEO_ID`
- `https://www.vimeo.com/VIDEO_ID`

### DailymotionProvider

URLs supportées :

- `https://www.dailymotion.com/video/VIDEO_ID`
- `https://dai.ly/VIDEO_ID`
- `https://www.dailymotion.com/embed/video/VIDEO_ID`

## EmbedProviderRegistry

Le registry chaîne les providers et délègue au premier qui supporte l'URL.

```php
$media = $registry->resolve('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
// → Media avec source=EMBED, provider=youtube, embedUrl=https://www.youtube.com/embed/dQw4w9WgXcQ
```

## Endpoint API

### POST /admin/media/embed

Crée un média EMBED depuis une URL.

```json
// Request
{
    "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
}

// Response 201
{
    "id": 42,
    "uuid": "...",
    "provider": "youtube",
    "embedUrl": "https://www.youtube.com/embed/dQw4w9WgXcQ"
}
```

Erreurs :

- `403` — CSRF token invalide
- `422` — URL manquante, provider non supporté, ou résolution échouée

### Exemple avec fetch

```javascript
fetch('/admin/media/embed', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({ url: 'https://youtu.be/dQw4w9WgXcQ' })
});
```

## Créer un provider custom

### 1. Implémenter l'interface

```php
// src/Embed/SoundCloudProvider.php (dans l'app)
namespace App\Embed;

use Neox\MediaBundle\Embed\EmbedProviderInterface;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;

final class SoundCloudProvider implements EmbedProviderInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        return str_ends_with($host, 'soundcloud.com');
    }

    public function resolve(string $url): ?Media
    {
        $media = new Media();
        $media->setSource(MediaSource::EMBED)
              ->setType(MediaType::AUDIO)
              ->setProvider($this->getName())
              ->setEmbedUrl('https://w.soundcloud.com/player/?url=' . urlencode($url))
              ->setUrl($url)
              ->setTitle('SoundCloud track');

        return $media;
    }

    public function getName(): string
    {
        return 'soundcloud';
    }
}
```

### 2. Enregistrer le service

```yaml
# config/services.yaml
services:
    App\Embed\SoundCloudProvider: ~
        # Le tag est auto-appliqué via #[AutoconfigureTag]
```

Le registry détecte automatiquement le nouveau provider.

## Flow

```text
URL → EmbedProviderRegistry
    → YoutubeProvider::supports()    ✓ → resolve() → Media
    → VimeoProvider::supports()      ✗
    → DailymotionProvider::supports() ✗
    → CustomProvider::supports()     ✗
```

## Règles

- Le bundle ne dépend d'aucune API externe (pas de clé API YouTube, etc.).
- L'extraction se fait par parsing d'URL, pas par appel API.
- Les providers sont auto-taggés : aucune config manuelle nécessaire.
- Le dev peut ajouter ses propres providers en implémentant `EmbedProviderInterface`.

[← Storage adapters](25-storage.md) · [Troubleshooting →](24-troubleshooting.md)
