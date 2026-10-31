<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Command;

use Neox\MediaBundle\Service\MediaFileSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'neox:media:sync-files', description: 'Synchronize files under the media home directory')]
final class SyncMediaFilesCommand extends Command
{
    public function __construct(private readonly MediaFileSynchronizer $synchronizer, private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->synchronizer->synchronize($this->projectDir.'/public/uploads/media');
        $output->writeln(sprintf('Imported: %d; skipped: %d', $result['imported'], $result['skipped']));

        return Command::SUCCESS;
    }
}
