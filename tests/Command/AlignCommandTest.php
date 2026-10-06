<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Tests\Command;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stolt\VersionAligner\Command\AlignCommand;
use Stolt\VersionAligner\Model\VersionAligner;
use Zenstruck\Console\Test\TestCommand;

class AlignCommandTest extends TestCase
{
    public function testExecuteSuccess(): void
    {
        $aligner = $this->createMock(VersionAligner::class);
        $aligner->expects($this->once())->method('align')->with(false)->willReturn(['src/Application.php']);

        TestCommand::for(new AlignCommand($aligner))
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Versions aligned successfully in src/Application.php.');
    }

    public function testExecuteDryRun(): void
    {
        $aligner = $this->createMock(VersionAligner::class);
        $aligner->expects($this->once())->method('align')->with(true)->willReturn(['src/Application.php']);

        TestCommand::for(new AlignCommand($aligner))
            ->execute('--dry-run')
            ->assertSuccessful()
            ->assertOutputContains('Versions would be aligned successfully in src/Application.php (dry-run).');
    }

    public function testExecuteSuccessNoModifications(): void
    {
        $aligner = $this->createMock(VersionAligner::class);
        $aligner->expects($this->once())->method('align')->with(false)->willReturn(null);

        TestCommand::for(new AlignCommand($aligner))
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Versions aligned successfully.');
    }

    public function testExecuteFailure(): void
    {
        $aligner = $this->createMock(VersionAligner::class);
        $aligner
            ->expects($this->once())
            ->method('align')
            ->willThrowException(new RuntimeException('Git tag and Changelog version do not match.'));

        TestCommand::for(new AlignCommand($aligner))
            ->execute()
            ->assertStatusCode(1)
            ->assertOutputContains('Git tag and Changelog version do not match.');
    }

    public function testExecuteMultipleFilesAligned(): void
    {
        $aligner = $this->createMock(VersionAligner::class);
        $aligner->expects($this->once())->method('align')->with(false)->willReturn([
            'bin/app',
            'src/Console/Application.php',
        ]);

        TestCommand::for(new AlignCommand($aligner))
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Versions aligned successfully in bin/app, src/Console/Application.php.');
    }

    public function testExecuteMultipleFilesDryRun(): void
    {
        $aligner = $this->createMock(VersionAligner::class);
        $aligner->expects($this->once())->method('align')->with(true)->willReturn([
            'bin/app',
            'src/Console/Application.php',
        ]);

        TestCommand::for(new AlignCommand($aligner))
            ->execute('--dry-run')
            ->assertSuccessful()
            ->assertOutputContains('Versions would be aligned successfully in bin/app, src/Console/Application.php (dry-run).');
    }
}
