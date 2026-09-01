<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Embed;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;

/**
 * YouTube embed provider.
 *
 * Supports:
 *   - https://www.youtube.com/watch?v=VIDEO_ID
 *   - https://youtu.be/VIDEO_ID
 *   - https://www.youtube.com/embed/VIDEO_ID
 *   - https://www.youtube.com/shorts/VIDEO_ID
 */
final class YoutubeProvider implements EmbedProviderInterface
{
    private const HOSTS = ['youtube.com', 'www.youtube.com', 'youtu.be', 'm.youtube.com'];
    private const EMBED_BASE = 'https://www.youtube.com/embed/';

    public function supports(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, self::HOSTS, true);
    }

    public function resolve(string $url): ?Media
    {
        $videoId = $this->extractVideoId($url);
        if ($videoId === null) {
            return null;
        }

        $media = new Media();
        $media->setSource(MediaSource::EMBED)
              ->setType(MediaType::VIDEO)
              ->setProvider($this->getName())
              ->setEmbedUrl(self::EMBED_BASE.$videoId)
              ->setUrl($url)
              ->setTitle('YouTube video '.$videoId);

        $metadata = $media->getMetadata();
        $metadata['video_id'] = $videoId;
        $metadata['thumbnail'] = sprintf('https://img.youtube.com/vi/%s/hqdefault.jpg', $videoId);
        $media->setMetadata($metadata);

        return $media;
    }

    public function getName(): string
    {
        return 'youtube';
    }

    private function extractVideoId(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = (string) parse_url($url, PHP_URL_QUERY);

        // youtu.be/VIDEO_ID
        if ($host === 'youtu.be') {
            $id = ltrim($path, '/');

            return $id !== '' ? $id : null;
        }

        // youtube.com/watch?v=VIDEO_ID
        if (str_starts_with($path, '/watch')) {
            parse_str($query, $params);
            $id = $params['v'] ?? null;

            return is_string($id) && $id !== '' ? $id : null;
        }

        // youtube.com/embed/VIDEO_ID
        if (str_starts_with($path, '/embed/')) {
            $id = substr($path, 8);

            return $id !== '' ? $id : null;
        }

        // youtube.com/shorts/VIDEO_ID
        if (str_starts_with($path, '/shorts/')) {
            $id = substr($path, 8);
            $id = explode('/', $id)[0];

            return $id !== '' ? $id : null;
        }

        return null;
    }
}
