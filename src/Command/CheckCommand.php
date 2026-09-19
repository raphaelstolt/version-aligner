<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Command;

use Stolt\VersionAligner\Model\VersionChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'check', description: 'Checks if application versions are aligned.')]
class CheckCommand extends Command
{
    private VersionChecker $versionChecker;

    public function __construct(?VersionChecker $versionChecker = null)
    {
        $this->versionChecker = $versionChecker ?? new VersionChecker();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'working-directory',
            \Symfony\Component\Console\Input\InputArgument::OPTIONAL,
            'The working directory to check versions in',
        );
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt or json)', 'txt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $input->getArgument('working-directory');
        if (is_string($dir)) {
            $this->versionChecker->setWorkingDirectory($dir);
        }

        $state = $this->versionChecker->check();

        if ($input->getOption('format') === 'json') {
            $output->writeln((string) json_encode([
                'isAligned' => $state->isAligned(),
                'versions' => [
                    'gitTag' => $state->gitTag,
                    'changelog' => $state->changelogVersion,
                    'application' => $state->applicationVersion,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $state->isAligned() ? Command::SUCCESS : Command::FAILURE;
        }

        $output->writeln('Version alignment');
        $output->writeln('');

        $normalize = fn(?string $v): ?string => $v !== null ? ltrim($v, 'v') : null;

        $versions = [
            'Git tag' => $state->gitTag,
            'CHANGELOG.md' => $state->changelogVersion,
            'Application' => $state->applicationVersion,
        ];

        $normalized = array_map($normalize, $versions);
        $filtered = array_filter($normalized, fn($v) => $v !== null);

        $canonical = null;
        if (count($filtered) > 0) {
            $counts = array_count_values($filtered);
            arsort($counts);
            $canonical = key($counts);
        }

        foreach ($versions as $label => $v) {
            if ($state->isAligned()) {
                $icon = '<info>✓</info>';
            } else {
                $icon = $v !== null && $normalize($v) === $canonical ? '<info>✓</info>' : '<fg=red>✗</fg=red>';
            }

            $displayValue = $v ?? 'missing';
            $output->writeln(sprintf('%s %-16s %s', $icon, $label, $displayValue));
        }

        $output->writeln('');

        if ($state->isAligned()) {
            $output->writeln('All versions are aligned.');
            return Command::SUCCESS;
        }

        $output->writeln('Version mismatch detected.');
        return Command::FAILURE;
    }
}
