<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Embed;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;

/**
 * Vimeo embed provider.
 *
 * Supports:
 *   - https://vimeo.com/VIDEO_ID
 *   - https://player.vimeo.com/video/VIDEO_ID
 *   - https://www.vimeo.com/VIDEO_ID
 */
final class VimeoProvider implements EmbedProviderInterface
{
    private const HOSTS = ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'];
    private const EMBED_BASE = 'https://player.vimeo.com/video/';

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
              ->setTitle('Vimeo video '.$videoId);

        $metadata = $media->getMetadata();
        $metadata['video_id'] = $videoId;
        $media->setMetadata($metadata);

        return $media;
    }

    public function getName(): string
    {
        return 'vimeo';
    }

    private function extractVideoId(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        // player.vimeo.com/video/VIDEO_ID
        if ($host === 'player.vimeo.com' && str_starts_with($path, '/video/')) {
            $id = substr($path, 7);
            $id = explode('/', $id)[0];

            return $id !== '' ? $id : null;
        }

        // vimeo.com/VIDEO_ID
        $segments = explode('/', trim($path, '/'));
        $id = $segments[0];
        if (is_numeric($id)) {
            return $id;
        }

        return null;
    }
}
