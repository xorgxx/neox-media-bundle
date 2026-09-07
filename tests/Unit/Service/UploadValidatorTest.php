<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Service;

use Neox\MediaBundle\Enum\MediaRiskLevel;
use Neox\MediaBundle\Service\UploadValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadValidatorTest extends TestCase
{
    public function testSafeAllowedFileIsNotSandboxed(): void
    {
        $file = $this->createUploadedFile('photo.jpg', 'image/jpeg', "\xFF\xD8\xFF\xE0JFIF");
        $validator = $this->createValidator();

        $result = $validator->validateWithRisk($file);

        self::assertSame(MediaRiskLevel::SAFE, $result->riskLevel);
        self::assertFalse($result->isBlocked());
        self::assertFalse($result->isSandboxed());
    }

    public function testSvgAllowedFileRequiresSandboxReview(): void
    {
        $file = $this->createUploadedFile('icon.svg', 'image/svg+xml', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $validator = $this->createValidator();

        $result = $validator->validateWithRisk($file);

        self::assertSame(MediaRiskLevel::SUSPICIOUS, $result->riskLevel);
        self::assertTrue($result->isSandboxed());
        self::assertSame('File extension "svg" requires sandbox review.', $result->message);
        self::assertNull($validator->validate($file));
    }

    public function testDangerousExtensionIsBlocked(): void
    {
        $file = $this->createUploadedFile('shell.php', 'text/x-php', '<?php echo 1;');
        $validator = $this->createValidator(allowedMime: ['text/x-php'], allowedExtensions: ['php']);

        $result = $validator->validateWithRisk($file);

        self::assertSame(MediaRiskLevel::BLOCKED, $result->riskLevel);
        self::assertTrue($result->isBlocked());
        self::assertSame('File extension "php" is forbidden.', $result->message);
    }

    private function createValidator(array $allowedMime = ['image/jpeg', 'image/svg+xml'], array $allowedExtensions = ['jpg', 'svg']): UploadValidator
    {
        return new UploadValidator(
            ['image' => 1048576, 'default' => 1048576],
            $allowedMime,
            $allowedExtensions,
            true,
            ['image/svg+xml'],
            ['svg'],
        );
    }

    private function createUploadedFile(string $name, string $mimeType, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'neox_media_test_');
        self::assertIsString($path);
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $mimeType, null, true);
    }
}