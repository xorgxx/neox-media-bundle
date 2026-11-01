<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Service;

use Neox\MediaBundle\Service\HlsPlaylistMetadataReader;
use PHPUnit\Framework\TestCase;

final class HlsPlaylistMetadataReaderTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/neox-hls-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/movie/720p', 0777, true);
        mkdir($this->root.'/movie/1080p', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        ) as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($this->root);
    }

    public function testReadsMasterVariantsAndDurationFromRendition(): void
    {
        file_put_contents($this->root.'/movie/master.m3u8', "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=3100000,RESOLUTION=1280x720,CODECS=\"avc1.64001f,mp4a.40.2\"\n720p/index.m3u8\n#EXT-X-STREAM-INF:BANDWIDTH=5500000,RESOLUTION=1920x1080\n1080p/index.m3u8\n");
        file_put_contents($this->root.'/movie/720p/index.m3u8', "#EXTM3U\n#EXTINF:6.0,\nsegment_0.ts\n#EXTINF:3.92,\nsegment_1.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->root.'/movie/1080p/index.m3u8', "#EXTM3U\n#EXTINF:10.0,\nsegment_0.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->root.'/movie/720p/segment_0.ts', '');
        file_put_contents($this->root.'/movie/1080p/segment_0.ts', '');

        $metadata = (new HlsPlaylistMetadataReader())->read($this->root.'/movie/master.m3u8', $this->root);

        self::assertSame('HLS', $metadata['format']);
        self::assertSame(1920, $metadata['width']);
        self::assertSame(1080, $metadata['height']);
        self::assertSame(10.0, $metadata['duration']);
        self::assertCount(2, $metadata['variants']);
        self::assertSame(['avc1.64001f,mp4a.40.2'], $metadata['codecs']);
    }

    public function testReadsResolutionFromSiblingMasterForVariantPlaylist(): void
    {
        file_put_contents($this->root.'/movie/master.m3u8', "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=3100000,RESOLUTION=1280x720\n720p/index.m3u8\n");
        file_put_contents($this->root.'/movie/720p/index.m3u8', "#EXTM3U\n#EXTINF:6.0,\nsegment_0.ts\n#EXT-X-ENDLIST\n");
        file_put_contents($this->root.'/movie/720p/segment_0.ts', '');

        $metadata = (new HlsPlaylistMetadataReader())->read($this->root.'/movie/720p/index.m3u8', $this->root);

        self::assertSame(1280, $metadata['width']);
        self::assertSame(720, $metadata['height']);
        self::assertSame(6.0, $metadata['duration']);
        self::assertSame('MPEG-TS', $metadata['container']);
    }

    public function testDoesNotReadManifestOutsideStorageRoot(): void
    {
        self::assertSame([], (new HlsPlaylistMetadataReader())->read(__FILE__, $this->root));
    }
}
