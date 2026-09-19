<?php

declare(strict_types=1);

namespace Stolt\VersionAligner\Tests\Model;

use PHPUnit\Framework\TestCase;
use Stolt\VersionAligner\Model\AlignmentState;

class AlignmentStateTest extends TestCase
{
    public function testIsAlignedReturnsTrueWhenAllMatch(): void
    {
        $state = new AlignmentState('1.0.0', 'v1.0.0', '1.0.0');
        $this->assertTrue($state->isAligned());
    }

    public function testIsAlignedReturnsFalseWhenOneMismatches(): void
    {
        $state = new AlignmentState('1.0.0', 'v1.0.0', '1.0.1');
        $this->assertFalse($state->isAligned());
    }

    public function testIsAlignedReturnsFalseWhenOneIsMissing(): void
    {
        $state = new AlignmentState('1.0.0', null, '1.0.0');
        $this->assertFalse($state->isAligned());
    }
}
