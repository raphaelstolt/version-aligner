<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Tests\Command;

use PHPUnit\Framework\TestCase;
use Stolt\VersionAligner\Command\CheckCommand;
use Stolt\VersionAligner\Model\AlignmentState;
use Stolt\VersionAligner\Model\VersionChecker;
use Zenstruck\Console\Test\TestCommand;

class CheckCommandTest extends TestCase
{
    public function testExecuteSuccessfulAlignment(): void
    {
        $checker = $this->createStub(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('1.0.0', 'v1.0.0', '1.0.0', [
            'src/Console/Application.php' => '1.0.0',
        ]));

        TestCommand::for(new CheckCommand($checker))
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('All versions are aligned.')
            ->assertOutputContains('✓ Git tag')
            ->assertOutputContains('✓ CHANGELOG.md')
            ->assertOutputContains('✓ Application')
            ->assertOutputContains('v1.0.0')
            ->assertOutputContains('1.0.0');
    }

    public function testExecuteMismatch(): void
    {
        $checker = $this->createStub(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('0.9.0', 'v1.0.0', '1.0.0', [
            'src/Console/Application.php' => '0.9.0',
        ]));

        TestCommand::for(new CheckCommand($checker))
            ->execute()
            ->assertStatusCode(1)
            ->assertOutputContains('Version mismatch detected.')
            ->assertOutputContains('✓ Git tag')
            ->assertOutputContains('✓ CHANGELOG.md')
            ->assertOutputContains('✗ Application')
            ->assertOutputContains('v1.0.0')
            ->assertOutputContains('1.0.0')
            ->assertOutputContains('0.9.0');
    }

    public function testExecuteJsonFormat(): void
    {
        $checker = $this->createStub(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('0.9.0', 'v1.0.0', '1.0.0', [
            'src/Console/Application.php' => '0.9.0',
        ]));

        TestCommand::for(new CheckCommand($checker))
            ->execute('--format=json')
            ->assertStatusCode(1)
            ->assertOutputContains('"isAligned": false')
            ->assertOutputContains('"gitTag": "v1.0.0"')
            ->assertOutputContains('"changelog": "1.0.0"')
            ->assertOutputContains('"application": "0.9.0"');
    }

    public function testExecuteMultipleApplicationVersions(): void
    {
        $checker = $this->createStub(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('1.0.0', 'v1.0.0', '1.0.0', [
            'bin/app' => '1.0.0',
            'src/Console/Application.php' => '1.0.0',
        ]));

        TestCommand::for(new CheckCommand($checker))
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('All versions are aligned.')
            ->assertOutputContains('Application (bin/app)')
            ->assertOutputContains('Application (src/Console/Application.php)');
    }

    public function testExecuteMultipleApplicationVersionsMismatch(): void
    {
        $checker = $this->createStub(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('0.9.0', 'v1.0.0', '1.0.0', [
            'bin/app' => '0.9.0',
            'src/Console/Application.php' => '1.0.0',
        ]));

        TestCommand::for(new CheckCommand($checker))
            ->execute()
            ->assertStatusCode(1)
            ->assertOutputContains('Version mismatch detected.')
            ->assertOutputContains('Application (bin/app)')
            ->assertOutputContains('Application (src/Console/Application.php)');
    }
}
