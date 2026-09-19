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
        $checker = $this->createMock(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('1.0.0', 'v1.0.0', '1.0.0'));

        TestCommand::for(new CheckCommand($checker))
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('All versions are aligned.')
            ->assertOutputContains('✓ Git tag          v1.0.0')
            ->assertOutputContains('✓ CHANGELOG.md     1.0.0')
            ->assertOutputContains('✓ Application      1.0.0');
    }

    public function testExecuteMismatch(): void
    {
        $checker = $this->createMock(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('0.9.0', 'v1.0.0', '1.0.0'));

        TestCommand::for(new CheckCommand($checker))
            ->execute()
            ->assertStatusCode(1)
            ->assertOutputContains('Version mismatch detected.')
            ->assertOutputContains('✓ Git tag          v1.0.0')
            ->assertOutputContains('✓ CHANGELOG.md     1.0.0')
            ->assertOutputContains('✗ Application      0.9.0');
    }

    public function testExecuteJsonFormat(): void
    {
        $checker = $this->createMock(VersionChecker::class);
        $checker->method('check')->willReturn(new AlignmentState('0.9.0', 'v1.0.0', '1.0.0'));

        TestCommand::for(new CheckCommand($checker))
            ->execute('--format=json')
            ->assertStatusCode(1)
            ->assertOutputContains('"isAligned": false')
            ->assertOutputContains('"gitTag": "v1.0.0"')
            ->assertOutputContains('"changelog": "1.0.0"')
            ->assertOutputContains('"application": "0.9.0"');
    }
}
