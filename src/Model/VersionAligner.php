<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Model;

use RuntimeException;

class VersionAligner
{
    private string $workingDirectory;
    private VersionChecker $versionChecker;

    public function __construct(?string $workingDirectory = null, ?VersionChecker $versionChecker = null)
    {
        $this->workingDirectory = $workingDirectory ?? (string) getcwd();
        $this->versionChecker = $versionChecker ?? new VersionChecker($this->workingDirectory);
    }

    public function setWorkingDirectory(string $workingDirectory): void
    {
        $this->workingDirectory = $workingDirectory;
        $this->versionChecker->setWorkingDirectory($workingDirectory);
    }

    public function align(bool $dryRun = false): void
    {
        $state = $this->versionChecker->check();

        $normalize = fn(?string $v): ?string => $v !== null ? ltrim($v, 'v') : null;

        $git = $normalize($state->gitTag);
        $changelog = $normalize($state->changelogVersion);
        $app = $normalize($state->applicationVersion);

        if ($git === null && $changelog === null) {
            throw new RuntimeException('No release version found (neither Git tag nor Changelog).');
        }

        if ($git !== null && $changelog !== null && $git !== $changelog) {
            throw new RuntimeException('Git tag and Changelog version do not match. Cannot safely align.');
        }

        $targetVersion = $changelog ?? $git;

        if ($app === $targetVersion) {
            return;
        }

        $this->updateApplicationVersion($targetVersion, $dryRun);
    }

    private function updateApplicationVersion(string $targetVersion, bool $dryRun): void
    {
        $composerJsonPath = $this->workingDirectory . DIRECTORY_SEPARATOR . 'composer.json';
        $filesToCheck = [
            'src/Console/Application.php',
            'src/Application.php',
            'src/Server.php',
        ];

        if (file_exists($composerJsonPath)) {
            $composerData = json_decode((string) file_get_contents($composerJsonPath), true);
            if (is_array($composerData) && isset($composerData['bin']) && is_array($composerData['bin'])) {
                foreach ($composerData['bin'] as $binFile) {
                    array_unshift($filesToCheck, $binFile);
                }
            }
        }

        foreach (array_unique($filesToCheck) as $fileToCheck) {
            $filePath = $this->workingDirectory . DIRECTORY_SEPARATOR . $fileToCheck;
            if (file_exists($filePath)) {
                $content = (string) file_get_contents($filePath);
                $pattern = '/([\'"])(v?\d+\.\d+\.\d+(?:-[a-zA-Z0-9\.]+)*)([\'"])/';

                if (preg_match($pattern, $content)) {
                    $newContent = preg_replace($pattern, '${1}' . $targetVersion . '${3}', $content, 1);
                    if ($newContent !== null && !$dryRun) {
                        file_put_contents($filePath, $newContent);
                    }
                    return;
                }
            }
        }

        throw new RuntimeException('Could not find the application version string to replace.');
    }
}
