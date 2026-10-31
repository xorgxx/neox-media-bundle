<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;
use Symfony\Component\Mime\MimeTypes;

final class MediaFileSynchronizer
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** @return array{imported:int, skipped:int} */
    public function synchronize(string $root): array
    {
        $imported = 0; $skipped = 0;
        if (!is_dir($root)) return ['imported' => 0, 'skipped' => 0];
        $known = [];
        foreach ($this->em->getRepository(Media::class)->findAll() as $existing) {
            $known[($existing->getStorageDir() ?? '').'/'.$existing->getFilename()] = $existing;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || in_array(strtolower($file->getExtension()), ['ts', 'm4s'], true)) { $skipped++; continue; }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($root, DIRECTORY_SEPARATOR)) + 1));
            $dir = dirname($relative);
            $dir = str_starts_with($relative, 'home/')
                ? ($dir === '.' ? 'home' : trim($dir, '/'))
                : ($dir === '.' ? 'home' : 'home/'.trim($dir, '/'));
            $name = basename($relative); $key = $dir.'/'.$name;
            $isHls = strtolower($file->getExtension()) === 'm3u8';
            if (isset($known[$key])) {
                $media = $known[$key];
                if ($isHls && $media->getType() !== MediaType::VIDEO) {
                    $media->setType(MediaType::VIDEO)->setMimeType('application/vnd.apple.mpegurl');
                    $imported++;
                } else {
                    $skipped++;
                }
                continue;
            }
            $media = new Media();
            $media->setFilename($name)->setOriginalName($name)->setSource(MediaSource::FILE)->setStorageDir($dir);
            $mime = $isHls ? 'application/vnd.apple.mpegurl' : (MimeTypes::getDefault()->guessMimeType($file->getPathname()) ?? 'application/octet-stream');
            $type = $isHls || str_starts_with($mime, 'video/') ? MediaType::VIDEO : (str_starts_with($mime, 'audio/') ? MediaType::AUDIO : (str_starts_with($mime, 'image/') ? MediaType::IMAGE : MediaType::OTHER));
            $media->setMimeType($mime)->setType($type)->setSize($file->getSize())->setTitle($isHls ? basename(dirname($file->getPathname())) : pathinfo($name, PATHINFO_FILENAME));
            $this->em->persist($media); $known[$key] = $media; $imported++;
        }
        $this->em->flush();
        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
