<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Twig;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Service\MediaUrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MediaExtension extends AbstractExtension
{
    public function __construct(private readonly MediaUrlGenerator $urls)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('neox_media_url', [$this, 'url']),
            new TwigFunction('neox_media_filter', [$this, 'filter']),
            new TwigFunction('neox_media_render', [$this, 'render'], ['is_safe' => ['html']]),
        ];
    }

    public function url(?Media $media): ?string
    {
        return $media ? $this->urls->url($media) : null;
    }

    public function filter(?Media $media, string $filterSet): ?string
    {
        return $media ? $this->urls->filter($media, $filterSet) : null;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function render(?Media $media, array $attributes = []): string
    {
        if (!$media) {
            return '';
        }
        $alt = htmlspecialchars((string) ($media->getAlt() ?? $media->getTitle() ?? ''), ENT_QUOTES);

        if ($media->getSource() === MediaSource::EMBED) {
            $embedUrl = $media->getEmbedUrl();
            if ($embedUrl === null || $embedUrl === '') {
                return $this->renderBrokenLink($media);
            }
            $embedUrl = htmlspecialchars($embedUrl, ENT_QUOTES);

            return sprintf('<iframe src="%s" frameborder="0" allowfullscreen style="width:100%%;aspect-ratio:16/9;border-radius:8px"></iframe>', $embedUrl);
        }

        $url = $this->url($media);
        if ($url === null || $url === '') {
            return $this->renderBrokenLink($media);
        }
        $url = htmlspecialchars($url, ENT_QUOTES);

        return match ($media->getType()) {
            MediaType::IMAGE => sprintf('<img src="%s" alt="%s" loading="lazy">', $url, $alt),
            MediaType::VIDEO => sprintf('<video src="%s" controls></video>', $url),
            MediaType::AUDIO => sprintf('<audio src="%s" controls></audio>', $url),
            default => sprintf('<a href="%s">%s</a>', $url, $alt ?: 'Média'),
        };
    }

    private function renderBrokenLink(Media $media): string
    {
        $title = htmlspecialchars((string) ($media->getTitle() ?? $media->getOriginalName() ?? 'Média #'.$media->getId()), ENT_QUOTES);

        return sprintf(
            '<div class="neox-media-broken" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:24px;border:1px dashed #dc2626;border-radius:8px;background:#fef2f2;color:#dc2626;text-align:center;min-height:120px"'
            .'><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">'
            .'<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>'
            .'<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'
            .'</svg>'
            .'<strong>%s</strong>'
            .'<small style="color:#991b1b">Lien inaccessible ou supprimé</small>'
            .'</div>',
            $title
        );
    }
}
