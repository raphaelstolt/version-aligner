<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Command;

use RuntimeException;
use Stolt\VersionAligner\Model\VersionAligner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'align', description: 'Aligns the application version when it differs from the release version.')]
class AlignCommand extends Command
{
    private VersionAligner $versionAligner;

    public function __construct(?VersionAligner $versionAligner = null)
    {
        $this->versionAligner = $versionAligner ?? new VersionAligner();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'working-directory',
            \Symfony\Component\Console\Input\InputArgument::OPTIONAL,
            'The working directory to align versions in',
        );
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Simulate the alignment without actually modifying files',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $input->getArgument('working-directory');
        if (is_string($dir)) {
            $this->versionAligner->setWorkingDirectory($dir);
        }

        $dryRun = (bool) $input->getOption('dry-run');

        try {
            $this->versionAligner->align($dryRun);
            if ($dryRun) {
                $output->writeln('Versions would be aligned successfully (dry-run).');
            } else {
                $output->writeln('Versions aligned successfully.');
            }

            return Command::SUCCESS;
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
