<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Service;

use Neox\MediaBundle\Service\UrlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UrlValidatorTest extends TestCase
{
    private function validator(): UrlValidator
    {
        return new UrlValidator(['http', 'https'], ['www.youtube.com']);
    }

    public function testAcceptsPublicUrl(): void
    {
        self::assertNull($this->validator()->validateUrl('https://example.com/media.jpg'));
    }

    public function testRejectsUrlWithoutAllowedScheme(): void
    {
        self::assertSame(
            'URL scheme "ftp" is not allowed. Allowed schemes: http, https.',
            $this->validator()->validateUrl('ftp://example.com/media.jpg')
        );
    }
    #[DataProvider('privateHostUrls')]
    public function testRejectsPrivateOrLocalUrlHosts(string $url): void
    {
        self::assertNotNull($this->validator()->validateUrl($url));
    }

    /** @return iterable<string, array{string}> */
    public static function privateHostUrls(): iterable
    {
        yield 'localhost' => ['https://localhost/media.jpg'];
        yield 'localhost subdomain' => ['https://app.localhost/media.jpg'];
        yield 'loopback ipv4' => ['https://127.0.0.1/media.jpg'];
        yield 'private ipv4' => ['https://192.168.1.10/media.jpg'];
        yield 'link local ipv4' => ['https://169.254.1.10/media.jpg'];
        yield 'loopback ipv6' => ['https://[::1]/media.jpg'];
        yield 'unique local ipv6' => ['https://[fc00::1]/media.jpg'];
    }

    public function testAcceptsAllowedEmbedProvider(): void
    {
        self::assertNull($this->validator()->validateEmbedUrl('https://www.youtube.com/embed/abc123'));
    }

    public function testRejectsLocalEmbedHostBeforeProviderAllowlist(): void
    {
        $validator = new UrlValidator(['https'], ['localhost']);

        self::assertSame(
            'Embed URL host "localhost" is not allowed.',
            $validator->validateEmbedUrl('https://localhost/embed/abc123')
        );
    }
}
