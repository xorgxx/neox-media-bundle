<?php

declare(strict_types=1);

namespace Neox\MediaBundle\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaStatus;
use Neox\MediaBundle\Message\ProcessMediaMessage;
use Neox\MediaBundle\Processor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessMediaMessageHandler
{
    /**
     * @param iterable<ProcessorInterface> $processors
     */
    public function __construct(
        private EntityManagerInterface $em,
        private iterable $processors,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(ProcessMediaMessage $message): void
    {
        $media = $this->em->find(Media::class, $message->mediaId);
        if (!$media) {
            $this->logger->warning('ProcessMediaMessage: Media not found', ['mediaId' => $message->mediaId]);

            return;
        }

        $media->setStatus(MediaStatus::PROCESSING);
        $this->em->flush();

        try {
            foreach ($this->processors as $processor) {
                if (!$processor->supports($media)) {
                    continue;
                }

                $this->logger->info('ProcessMediaMessage: running processor', [
                    'mediaId' => $media->getId(),
                    'processor' => $processor::class,
                ]);

                $processor->process($media);
            }

            $media->setStatus(MediaStatus::READY);
            $this->em->flush();

            $this->logger->info('ProcessMediaMessage: media ready', ['mediaId' => $media->getId()]);
        } catch (\Throwable $e) {
            $media->setStatus(MediaStatus::FAILED);
            $this->em->flush();

            $this->logger->error('ProcessMediaMessage: processing failed', [
                'mediaId' => $media->getId(),
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }
}
