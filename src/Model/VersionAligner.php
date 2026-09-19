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

        if (!file_exists($composerJsonPath)) {
            throw new RuntimeException('composer.json not found.');
        }

        $composerData = json_decode((string) file_get_contents($composerJsonPath), true);

        if (is_array($composerData) && isset($composerData['bin']) && is_array($composerData['bin'])) {
            foreach ($composerData['bin'] as $binFile) {
                $binPath = $this->workingDirectory . DIRECTORY_SEPARATOR . $binFile;
                if (file_exists($binPath)) {
                    $content = (string) file_get_contents($binPath);
                    $pattern = '/((?:new\s+[a-zA-Z0-9_\\\\]+\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*|->setVersion\s*\(\s*|const\s+[A-Z0-9_]+\s*=\s*|define\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*)[\'"])(v?\d+\.\d+\.\d+(?:-[a-zA-Z0-9\.]+)*)([\'"])/i';

                    if (preg_match($pattern, $content)) {
                        $newContent = preg_replace($pattern, '${1}' . $targetVersion . '${3}', $content);
                        if ($newContent !== null && !$dryRun) {
                            file_put_contents($binPath, $newContent);
                        }
                        return;
                    }
                }
            }
        }

        throw new RuntimeException('Could not find the application version string to replace.');
    }
}
