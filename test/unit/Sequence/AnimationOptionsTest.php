<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Sequence;

use Horde\Image\Sequence\AnimationOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnimationOptions::class)]
final class AnimationOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $opts = new AnimationOptions();

        self::assertSame(0, $opts->loopCount);
        self::assertSame(100, $opts->defaultDelay);
        self::assertTrue($opts->optimize);
    }

    public function testCustomValues(): void
    {
        $opts = new AnimationOptions(loopCount: 3, defaultDelay: 50, optimize: false);

        self::assertSame(3, $opts->loopCount);
        self::assertSame(50, $opts->defaultDelay);
        self::assertFalse($opts->optimize);
    }
}
