# 26. Embed Providers

## Purpose

The `EMBED` source covers media embedded via an external provider: YouTube, Vimeo, Dailymotion, SoundCloud, etc.

The bundle provides:

- an `EmbedProviderInterface` interface;
- 3 ready-to-use providers: YouTube, Vimeo, Dailymotion;
- an `EmbedProviderRegistry` that chains providers;
- an API endpoint to create an EMBED media from a URL.

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

The `#[AutoconfigureTag]` attribute automatically tags any implementation with `neox_media.embed_provider`.

## Provided Providers

### YoutubeProvider

Supported URLs:

- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://www.youtube.com/embed/VIDEO_ID`
- `https://www.youtube.com/shorts/VIDEO_ID`

Extracts the video ID, generates the embed URL `https://www.youtube.com/embed/VIDEO_ID`, and stores the thumbnail in `metadata['thumbnail']`.

### VimeoProvider

Supported URLs:

- `https://vimeo.com/VIDEO_ID`
- `https://player.vimeo.com/video/VIDEO_ID`
- `https://www.vimeo.com/VIDEO_ID`

### DailymotionProvider

Supported URLs:

- `https://www.dailymotion.com/video/VIDEO_ID`
- `https://dai.ly/VIDEO_ID`
- `https://www.dailymotion.com/embed/video/VIDEO_ID`

## EmbedProviderRegistry

The registry chains providers and delegates to the first one that supports the URL.

```php
$media = $registry->resolve('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
// → Media with source=EMBED, provider=youtube, embedUrl=https://www.youtube.com/embed/dQw4w9WgXcQ
```

## API Endpoint

### POST /admin/media/embed

Creates an EMBED media from a URL.

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

Errors:

- `403` — Invalid CSRF token
- `422` — Missing URL, unsupported provider, or resolution failed

### Example with fetch

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

## Creating a Custom Provider

### 1. Implement the interface

```php
// src/Embed/SoundCloudProvider.php (in the app)
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

### 2. Register the service

```yaml
# config/services.yaml
services:
    App\Embed\SoundCloudProvider: ~
        # The tag is auto-applied via #[AutoconfigureTag]
```

The registry automatically detects the new provider.

## Flow

```text
URL → EmbedProviderRegistry
    → YoutubeProvider::supports()    ✓ → resolve() → Media
    → VimeoProvider::supports()      ✗
    → DailymotionProvider::supports() ✗
    → CustomProvider::supports()     ✗
```

## Rules

- The bundle does not depend on any external API (no YouTube API key, etc.).
- Extraction is done by URL parsing, not by API call.
- Providers are auto-tagged: no manual config needed.
- Developers can add their own providers by implementing `EmbedProviderInterface`.

[← Storage adapters](25-storage.md) · [Troubleshooting →](24-troubleshooting.md)
