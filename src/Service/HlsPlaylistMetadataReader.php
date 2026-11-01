<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Service;

/** Reads metadata already declared by local HLS playlists; it does not probe or transcode media. */
final class HlsPlaylistMetadataReader
{
    /** @return array{format: string, container?: string, codecs?: list<string>, variants?: list<array{uri: string, width?: int, height?: int, bandwidth?: int, codecs?: string}>, width?: int, height?: int, duration?: float}|array{} */
    public function read(string $manifestPath, string $storageRoot): array
    {
        $root = realpath($storageRoot);
        $manifest = realpath($manifestPath);
        if ($root === false || $manifest === false || !str_starts_with($manifest, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            return [];
        }

        $contents = @file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($contents === false || ($contents[0] ?? null) !== '#EXTM3U') {
            return [];
        }

        $metadata = ['format' => 'HLS'];
        $variants = $this->parseVariants($contents);
        $playlistPath = $manifest;
        $selectedVariant = null;

        // Rendition playlists don't repeat their resolution. Read the sibling
        // master when it declares this playlist.
        if ($variants === []) {
            $parentManifest = dirname(dirname($manifest)).DIRECTORY_SEPARATOR.'master.m3u8';
            if (is_file($parentManifest)) {
                $parentLines = @file($parentManifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($this->parseVariants($parentLines) as $variant) {
                    $candidate = $this->resolveLocalUri(dirname($parentManifest), $variant['uri'], $root);
                    if ($candidate === $manifest) {
                        $selectedVariant = $variant;
                        $variants = [$variant];
                        break;
                    }
                }
            }
        }

        if ($variants !== []) {
            $metadata['variants'] = $variants;
            $widths = array_column($variants, 'width');
            $heights = array_column($variants, 'height');
            $metadata['width'] = $selectedVariant['width'] ?? (empty($widths) ? null : max($widths));
            $metadata['height'] = $selectedVariant['height'] ?? (empty($heights) ? null : max($heights));
            $codecs = array_values(array_unique(array_filter(array_column($variants, 'codecs'))));
            if ($codecs !== []) {
                $metadata['codecs'] = $codecs;
            }
        }

        $duration = $this->readDuration($contents);
        if ($duration === null && $variants !== []) {
            $durations = [];
            foreach ($variants as $variant) {
                $variantPath = $this->resolveLocalUri(dirname($manifest), $variant['uri'], $root);
                if ($variantPath !== null) {
                    $variantLines = @file($variantPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                    $variantDuration = $this->readDuration($variantLines);
                    if ($variantDuration !== null) {
                        $durations[] = $variantDuration;
                    }
                }
            }
            $duration = $durations === [] ? null : max($durations);
        }
        if ($duration !== null) {
            $metadata['duration'] = $duration;
        }

        $container = $this->readContainer($contents);
        if ($container === null) {
            foreach ($variants as $variant) {
                $variantPath = $this->resolveLocalUri(dirname($manifest), $variant['uri'], $root);
                if ($variantPath !== null) {
                    $variantLines = @file($variantPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                    $container = $this->readContainer($variantLines);
                    if ($container !== null) {
                        break;
                    }
                }
            }
        }
        if ($container !== null) {
            $metadata['container'] = $container;
        }

        return array_filter($metadata, static fn (mixed $value): bool => $value !== null);
    }

    /** @param list<string> $lines
     * @return list<array{uri: string, width?: int, height?: int, bandwidth?: int, codecs?: string}>
     */
    private function parseVariants(array $lines): array
    {
        $variants = [];
        $attributes = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#EXT-X-STREAM-INF:')) {
                $attributes = $this->parseAttributes(substr($line, strlen('#EXT-X-STREAM-INF:')));
                continue;
            }
            if ($attributes !== null && $line !== '' && !str_starts_with($line, '#')) {
                $variant = ['uri' => $line];
                if (preg_match('/^(\d+)x(\d+)$/i', $attributes['RESOLUTION'] ?? '', $resolution)) {
                    $variant['width'] = (int) $resolution[1];
                    $variant['height'] = (int) $resolution[2];
                }
                if (isset($attributes['BANDWIDTH']) && ctype_digit($attributes['BANDWIDTH'])) {
                    $variant['bandwidth'] = (int) $attributes['BANDWIDTH'];
                }
                if (isset($attributes['CODECS'])) {
                    $variant['codecs'] = $attributes['CODECS'];
                }
                $variants[] = $variant;
                $attributes = null;
            }
        }

        return $variants;
    }

    /** @return array<string, string> */
    private function parseAttributes(string $line): array
    {
        preg_match_all('/([A-Z0-9-]+)=((?:"[^"]*")|[^,]*)/i', $line, $matches, PREG_SET_ORDER);
        $attributes = [];
        foreach ($matches as $match) {
            $attributes[strtoupper($match[1])] = trim($match[2], '"');
        }

        return $attributes;
    }

    /** @param list<string> $lines */
    private function readDuration(array $lines): ?float
    {
        $duration = 0.0;
        $found = false;
        foreach ($lines as $line) {
            if (preg_match('/^#EXTINF:([0-9]+(?:\.[0-9]+)?)/', trim($line), $matches)) {
                $duration += (float) $matches[1];
                $found = true;
            }
        }

        return $found ? $duration : null;
    }

    /** @param list<string> $lines */
    private function readContainer(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/^([^#][^,]*\.(?:ts|m4s|mp4))(?:[?#].*)?$/i', trim($line), $matches)) {
                return strtolower(pathinfo($matches[1], PATHINFO_EXTENSION)) === 'ts' ? 'MPEG-TS' : 'fMP4';
            }
        }

        return null;
    }

    private function resolveLocalUri(string $directory, string $uri, string $root): ?string
    {
        if ($uri === '' || str_contains($uri, '://') || str_starts_with($uri, '/')) {
            return null;
        }
        $path = realpath($directory.DIRECTORY_SEPARATOR.$uri);
        if ($path === false || !str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) || !is_file($path)) {
            return null;
        }

        return $path;
    }
}
