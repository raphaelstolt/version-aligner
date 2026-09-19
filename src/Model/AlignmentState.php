<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Model;

final class AlignmentState
{
    public function __construct(
        public readonly ?string $applicationVersion,
        public readonly ?string $gitTag,
        public readonly ?string $changelogVersion,
    ) {}

    public function isAligned(): bool
    {
        if ($this->applicationVersion === null || $this->gitTag === null || $this->changelogVersion === null) {
            return false;
        }

        $normalize = fn(string $version): string => ltrim($version, 'v');

        $app = $normalize($this->applicationVersion);
        $git = $normalize($this->gitTag);
        $changelog = $normalize($this->changelogVersion);

        return $app === $git && $git === $changelog;
    }
}
