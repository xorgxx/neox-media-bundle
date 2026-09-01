<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Embed;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;

/**
 * Dailymotion embed provider.
 *
 * Supports:
 *   - https://www.dailymotion.com/video/VIDEO_ID
 *   - https://dai.ly/VIDEO_ID
 *   - https://www.dailymotion.com/embed/video/VIDEO_ID
 */
final class DailymotionProvider implements EmbedProviderInterface
{
    private const HOSTS = ['dailymotion.com', 'www.dailymotion.com', 'dai.ly'];
    private const EMBED_BASE = 'https://www.dailymotion.com/embed/video/';

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
              ->setTitle('Dailymotion video '.$videoId);

        $metadata = $media->getMetadata();
        $metadata['video_id'] = $videoId;
        $metadata['thumbnail'] = sprintf('https://www.dailymotion.com/thumbnail/video/%s', $videoId);
        $media->setMetadata($metadata);

        return $media;
    }

    public function getName(): string
    {
        return 'dailymotion';
    }

    private function extractVideoId(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        // dai.ly/VIDEO_ID
        if ($host === 'dai.ly') {
            $id = ltrim($path, '/');

            return $id !== '' ? $id : null;
        }

        // dailymotion.com/embed/video/VIDEO_ID
        if (str_starts_with($path, '/embed/video/')) {
            $id = substr($path, 13);
            $id = explode('/', $id)[0];

            return $id !== '' ? $id : null;
        }

        // dailymotion.com/video/VIDEO_ID
        if (str_starts_with($path, '/video/')) {
            $id = substr($path, 7);
            $id = explode('/', $id)[0];

            return $id !== '' ? $id : null;
        }

        return null;
    }
}
