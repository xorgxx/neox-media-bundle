<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Entity;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaRiskLevel;
use PHPUnit\Framework\TestCase;

final class MediaSandboxMetadataTest extends TestCase
{
    public function testSandboxStateIsStoredInMetadata(): void
    {
        $media = new Media();

        self::assertFalse($media->isSandboxed());
        self::assertSame(MediaRiskLevel::SAFE, $media->getRiskLevel());
        self::assertNull($media->getRiskReason());

        $media->setSandboxed(true)
            ->setRiskLevel(MediaRiskLevel::SUSPICIOUS)
            ->setRiskReason('SVG uploadé placé en sandbox');

        self::assertTrue($media->isSandboxed());
        self::assertSame(MediaRiskLevel::SUSPICIOUS, $media->getRiskLevel());
        self::assertSame('SVG uploadé placé en sandbox', $media->getRiskReason());
        self::assertSame([
            'sandboxed' => true,
            'risk_level' => 'suspicious',
            'risk_reason' => 'SVG uploadé placé en sandbox',
        ], $media->getMetadata());
    }

    public function testApprovingSandboxClearsBlockingMetadata(): void
    {
        $media = (new Media())
            ->setSandboxed(true)
            ->setRiskLevel(MediaRiskLevel::SUSPICIOUS)
            ->setRiskReason('Archive douteuse');

        $media->approveSandbox();

        self::assertFalse($media->isSandboxed());
        self::assertSame(MediaRiskLevel::SAFE, $media->getRiskLevel());
        self::assertNull($media->getRiskReason());
        self::assertSame(['risk_level' => 'safe'], $media->getMetadata());
    }
}