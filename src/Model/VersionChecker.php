<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Model;

class VersionChecker
{
    private string $workingDirectory;

    public function __construct(?string $workingDirectory = null)
    {
        $this->workingDirectory = $workingDirectory ?? (string) getcwd();
    }

    public function setWorkingDirectory(string $workingDirectory): void
    {
        $this->workingDirectory = $workingDirectory;
    }

    public function check(): AlignmentState
    {
        $applicationVersions = $this->getApplicationVersions();
        $applicationVersion = empty($applicationVersions) ? null : reset($applicationVersions);

        return new AlignmentState(
            $applicationVersion,
            $this->getLatestGitTag(),
            $this->getChangelogVersion(),
            $applicationVersions,
        );
    }

    public function getLatestGitTag(): ?string
    {
        $output = [];
        $returnCode = 0;
        exec(
            sprintf('cd %s && git describe --tags --abbrev=0 2>/dev/null', escapeshellarg($this->workingDirectory)),
            $output,
            $returnCode,
        );

        if ($returnCode === 0 && isset($output[0])) {
            return trim($output[0]);
        }

        return null;
    }

    public function getChangelogVersion(): ?string
    {
        $changelogPath = null;
        $files = scandir($this->workingDirectory) ?: [];

        foreach ($files as $file) {
            $path = $this->workingDirectory . DIRECTORY_SEPARATOR . $file;
            if (
                is_file($path)
                && preg_match('/^(?:CHANGELOG|CHANGES|HISTORY|RELEASE(?:_NOTES)?)(?:\.md|\.txt|\.rst)?$/i', $file)
            ) {
                $changelogPath = $path;
                break;
            }
        }

        if ($changelogPath === null) {
            return null;
        }

        $content = file_get_contents($changelogPath);
        if ($content === false) {
            return null;
        }

        if (preg_match(
            '/^## \[?(v?\d+\.\d+\.\d+(?:-[a-zA-Z0-9\.]+)*)\]?(?: - \d{4}-\d{2}-\d{2})?$/m',
            $content,
            $matches,
        )) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Returns all application versions found, keyed by file path.
     */
    public function getApplicationVersions(): array
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

        $versions = [];
        foreach (array_unique($filesToCheck) as $fileToCheck) {
            $filePath = $this->workingDirectory . DIRECTORY_SEPARATOR . $fileToCheck;
            if (file_exists($filePath)) {
                $content = (string) file_get_contents($filePath);
                if (preg_match('/[\'"](v?\d+\.\d+\.\d+(?:-[a-zA-Z0-9\.]+)*)[\'"]/', $content, $matches)) {
                    $versions[$fileToCheck] = $matches[1];
                }
            }
        }

        return $versions;
    }

    public function getApplicationVersion(): ?string
    {
        $versions = $this->getApplicationVersions();
        if (empty($versions)) {
            return null;
        }
        return reset($versions);
    }
}
