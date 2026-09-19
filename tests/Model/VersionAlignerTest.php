<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Tests\Model;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stolt\VersionAligner\Model\AlignmentState;
use Stolt\VersionAligner\Model\VersionAligner;
use Stolt\VersionAligner\Model\VersionChecker;

class VersionAlignerTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = __DIR__ . '/../fixtures/version-aligner';
        if (!is_dir($this->fixtureDir)) {
            mkdir($this->fixtureDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->fixtureDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testAlignThrowsExceptionWhenNoReleaseVersionFound(): void
    {
        $checker = $this->createMock(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('1.0.0', null, null));

        $aligner = new VersionAligner($this->fixtureDir, $checker);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No release version found');
        $aligner->align();
    }

    public function testAlignThrowsExceptionWhenGitAndChangelogMismatch(): void
    {
        $checker = $this->createMock(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('1.0.0', 'v1.1.0', '1.2.0'));

        $aligner = new VersionAligner($this->fixtureDir, $checker);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Git tag and Changelog version do not match');
        $aligner->align();
    }

    public function testAlignUpdatesApplicationVersionSuccessfully(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['bin' => ['bin/app']]));
        mkdir($this->fixtureDir . '/bin');
        $binContent = <<<EOF
            #!/usr/bin/env php
            <?php
            \$server = new \PhpMcp\Server('mcp-tool', '1.0.0');
            EOF;
        file_put_contents($this->fixtureDir . '/bin/app', $binContent);

        $checker = $this->createMock(VersionChecker::class);
        // Current state: app is 1.0.0, release is 2.0.0
        $checker->method('check')->willReturn(new AlignmentState('1.0.0', 'v2.0.0', '2.0.0'));

        $aligner = new VersionAligner($this->fixtureDir, $checker);
        $aligner->align();

        $updatedContent = (string) file_get_contents($this->fixtureDir . '/bin/app');
        $this->assertStringContainsString("'2.0.0'", $updatedContent);
        $this->assertStringNotContainsString("'1.0.0'", $updatedContent);
    }

    public function testAlignDoesNotModifyFilesOnDryRun(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['bin' => ['bin/app']]));
        mkdir($this->fixtureDir . '/bin');
        $binContent = <<<EOF
            #!/usr/bin/env php
            <?php
            \$server = new \PhpMcp\Server('mcp-tool', '1.0.0');
            EOF;
        file_put_contents($this->fixtureDir . '/bin/app', $binContent);

        $checker = $this->createMock(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('1.0.0', 'v2.0.0', '2.0.0'));

        $aligner = new VersionAligner($this->fixtureDir, $checker);
        $aligner->align(true);

        $updatedContent = (string) file_get_contents($this->fixtureDir . '/bin/app');
        $this->assertStringContainsString("'1.0.0'", $updatedContent); // Remained unchanged
    }
}
