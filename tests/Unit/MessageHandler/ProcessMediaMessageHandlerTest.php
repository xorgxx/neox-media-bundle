<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaStatus;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Message\ProcessMediaMessage;
use Neox\MediaBundle\MessageHandler\ProcessMediaMessageHandler;
use Neox\MediaBundle\Processor\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ProcessMediaMessageHandlerTest extends TestCase
{
    public function testSetsProcessingThenReadyWhenNoProcessors(): void
    {
        $media = $this->createMedia();
        $media->setStatus(MediaStatus::PENDING);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($media);
        $em->expects(self::atLeast(2))->method('flush');

        $handler = new ProcessMediaMessageHandler($em, [], new NullLogger());
        $handler(new ProcessMediaMessage(1));

        self::assertSame(MediaStatus::READY, $media->getStatus());
    }

    public function testSetsFailedWhenProcessorThrowsException(): void
    {
        $media = $this->createMedia();
        $media->setStatus(MediaStatus::PENDING);

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->method('supports')->willReturn(true);
        $processor->method('process')->willThrowException(new \RuntimeException('Processing error'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($media);
        $em->expects(self::atLeast(2))->method('flush');

        $handler = new ProcessMediaMessageHandler($em, [$processor], new NullLogger());
        $handler(new ProcessMediaMessage(1));

        self::assertSame(MediaStatus::FAILED, $media->getStatus());
    }

    public function testSkipsProcessorsThatDontSupport(): void
    {
        $media = $this->createMedia();

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->method('supports')->willReturn(false);
        $processor->expects(self::never())->method('process');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($media);

        $handler = new ProcessMediaMessageHandler($em, [$processor], new NullLogger());
        $handler(new ProcessMediaMessage(1));

        self::assertSame(MediaStatus::READY, $media->getStatus());
    }

    public function testReturnsEarlyWhenMediaNotFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn(null);
        $em->expects(self::never())->method('flush');

        $handler = new ProcessMediaMessageHandler($em, [], new NullLogger());
        $handler(new ProcessMediaMessage(999));

        // No exception thrown, just returns silently
        $this->addToAssertionCount(1);
    }

    private function createMedia(): Media
    {
        $media = new Media();
        $media->setSource(MediaSource::FILE);
        $media->setType(MediaType::IMAGE);

        return $media;
    }
}
