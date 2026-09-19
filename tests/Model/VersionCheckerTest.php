<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Tests\Model;

use PHPUnit\Framework\TestCase;
use Stolt\VersionAligner\Model\VersionChecker;

class VersionCheckerTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = __DIR__ . '/../fixtures/version-checker';
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

    public function testGetChangelogVersion(): void
    {
        $content = <<<EOF
            # Changelog
            ## [Unreleased]
            ### Added
            - Something

            ## [1.2.3] - 2026-09-19
            ### Added
            - Something else
            EOF;
        file_put_contents($this->fixtureDir . '/CHANGELOG.md', $content);

        $checker = new VersionChecker($this->fixtureDir);
        $this->assertSame('1.2.3', $checker->getChangelogVersion());
    }

    public function testGetChangelogVersionWithAlternativeName(): void
    {
        $content = <<<EOF
            # History

            ## [2.1.0] - 2026-09-20
            ### Fixed
            - Bug fix
            EOF;
        file_put_contents($this->fixtureDir . '/HISTORY.txt', $content);

        $checker = new VersionChecker($this->fixtureDir);
        $this->assertSame('2.1.0', $checker->getChangelogVersion());
    }

    public function testGetApplicationVersionFromBin(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['bin' => ['bin/app']]));
        mkdir($this->fixtureDir . '/bin');
        $binContent = <<<EOF
            #!/usr/bin/env php
            <?php
            use Symfony\Component\Console\Application;
            \$app = new Application('my-app', '2.0.1-beta');
            EOF;
        file_put_contents($this->fixtureDir . '/bin/app', $binContent);

        $checker = new VersionChecker($this->fixtureDir);
        $this->assertSame('2.0.1-beta', $checker->getApplicationVersion());
    }

    public function testGetApplicationVersionFromGenericApp(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['bin' => ['bin/app']]));
        mkdir($this->fixtureDir . '/bin');
        $binContent = <<<EOF
            #!/usr/bin/env php
            <?php
            \$server = new \PhpMcp\Server('mcp-tool', '3.1.4');
            EOF;
        file_put_contents($this->fixtureDir . '/bin/app', $binContent);

        $checker = new VersionChecker($this->fixtureDir);
        $this->assertSame('3.1.4', $checker->getApplicationVersion());
    }

    public function testGetApplicationVersionFromDefine(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['bin' => ['bin/app']]));
        if (!is_dir($this->fixtureDir . '/bin')) {
            mkdir($this->fixtureDir . '/bin');
        }
        $binContent = <<<EOF
            #!/usr/bin/env php
            <?php
            define('APP_VERSION', '1.8.0');
            EOF;
        file_put_contents($this->fixtureDir . '/bin/app', $binContent);

        $checker = new VersionChecker($this->fixtureDir);
        $this->assertSame('1.8.0', $checker->getApplicationVersion());
    }

    public function testGetApplicationVersionFromSrcConsoleApplicationPhp(): void
    {
        // No bin defined, so it will fallback to checking common paths.
        file_put_contents($this->fixtureDir . '/composer.json', json_encode([]));

        $srcDir = $this->fixtureDir . '/src/Console';
        mkdir($srcDir, 0777, true);

        $content = <<<EOF
            <?php
            namespace App\Console;

            class Application extends \Symfony\Component\Console\Application
            {
                public function __construct()
                {
                    parent::__construct('My App', '4.2.0');
                }
            }
            EOF;
        file_put_contents($srcDir . '/Application.php', $content);

        $checker = new VersionChecker($this->fixtureDir);
        $this->assertSame('4.2.0', $checker->getApplicationVersion());
    }
}
