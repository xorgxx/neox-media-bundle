<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Processor;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;

/**
 * Extracts EXIF data (dimensions, orientation, GPS, camera info) from image files.
 * Uses PHP's native exif_read_data() — no external dependency.
 */
final class ExifProcessor implements ProcessorInterface
{
    public function supports(Media $media): bool
    {
        return $media->getType() === MediaType::IMAGE
            && $media->getSource() === MediaSource::FILE
            && $media->getFilename() !== null;
    }

    public function process(Media $media): void
    {
        $filepath = $this->resolveFilepath($media);
        if ($filepath === null || !file_exists($filepath)) {
            return;
        }

        if (!function_exists('exif_read_data')) {
            return;
        }

        $exif = @exif_read_data($filepath, 'ANY_TAG', true);
        if ($exif === false) {
            return;
        }

        $metadata = $media->getMetadata();

        if (isset($exif['COMPUTED']['Width'], $exif['COMPUTED']['Height'])) {
            $media->setWidth((int) $exif['COMPUTED']['Width']);
            $media->setHeight((int) $exif['COMPUTED']['Height']);
        }

        if (isset($exif['IFD0']['Make'])) {
            $metadata['camera_make'] = $exif['IFD0']['Make'];
        }
        if (isset($exif['IFD0']['Model'])) {
            $metadata['camera_model'] = $exif['IFD0']['Model'];
        }
        if (isset($exif['IFD0']['DateTime'])) {
            $metadata['taken_at'] = $exif['IFD0']['DateTime'];
        }
        if (isset($exif['IFD0']['Orientation'])) {
            $metadata['orientation'] = (int) $exif['IFD0']['Orientation'];
        }

        $gps = $this->extractGps($exif);
        if ($gps !== null) {
            $metadata['gps'] = $gps;
        }

        $media->setMetadata($metadata);
    }

    /**
     * @param array<string, mixed> $exif
     *
     * @return array{lat: float, lng: float}|null
     */
    private function extractGps(array $exif): ?array
    {
        if (!isset($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef'], $exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef'])) {
            return null;
        }

        $lat = $this->gpsToDecimal($exif['GPS']['GPSLatitude']);
        $lng = $this->gpsToDecimal($exif['GPS']['GPSLongitude']);

        if ($exif['GPS']['GPSLatitudeRef'] === 'S') {
            $lat *= -1;
        }
        if ($exif['GPS']['GPSLongitudeRef'] === 'W') {
            $lng *= -1;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /** @param array<int, string> $coordinate */
    private function gpsToDecimal(array $coordinate): float
    {
        $degrees = $this->gpsDivide($coordinate[0] ?? '0/1');
        $minutes = $this->gpsDivide($coordinate[1] ?? '0/1');
        $seconds = $this->gpsDivide($coordinate[2] ?? '0/1');

        return $degrees + ($minutes / 60) + ($seconds / 3600);
    }

    private function gpsDivide(string $value): float
    {
        $parts = explode('/', $value);
        if (count($parts) === 2 && (float) $parts[1] !== 0.0) {
            return (float) $parts[0] / (float) $parts[1];
        }

        return (float) $value;
    }

    private function resolveFilepath(Media $media): ?string
    {
        $filename = $media->getFilename();
        if ($filename === null) {
            return null;
        }

        $projectDir = rtrim((string) ($_ENV['KERNEL_PROJECT_DIR'] ?? dirname(__DIR__, 4)), '/');
        $path = $projectDir.'/public/uploads/media/'.$filename;

        return file_exists($path) ? $path : null;
    }
}
