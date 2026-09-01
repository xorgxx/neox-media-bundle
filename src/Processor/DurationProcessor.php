<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Processor;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;

/**
 * Detects video/audio duration using FFmpeg if php-ffmpeg is available.
 * Gracefully skips if the library is not installed.
 */
final class DurationProcessor implements ProcessorInterface
{
    public function supports(Media $media): bool
    {
        return in_array($media->getType(), [MediaType::VIDEO, MediaType::AUDIO], true)
            && $media->getSource() === MediaSource::FILE
            && $media->getFilename() !== null;
    }

    public function process(Media $media): void
    {
        if (!class_exists(\FFMpeg\FFMpeg::class)) {
            return;
        }

        $filepath = $this->resolveFilepath($media);
        if ($filepath === null || !file_exists($filepath)) {
            return;
        }

        try {
            $ffmpeg = \FFMpeg\FFMpeg::create();
            $video = $ffmpeg->open($filepath);
            $duration = $video->getFormat()->get('duration');

            if ($duration !== null) {
                $media->setDuration((float) $duration);
            }
        } catch (\Throwable) {
            // FFmpeg not available or file unreadable — skip silently
        }
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
