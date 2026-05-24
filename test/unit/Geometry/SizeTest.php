<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Geometry;

use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Size::class)]
final class SizeTest extends TestCase
{
    public function testConstruction(): void
    {
        $size = new Size(800.0, 600.0);

        self::assertSame(800.0, $size->width);
        self::assertSame(600.0, $size->height);
    }

    public function testScale(): void
    {
        $size = new Size(100.0, 50.0);
        $scaled = $size->scale(2.0);

        self::assertSame(200.0, $scaled->width);
        self::assertSame(100.0, $scaled->height);
        // Original unchanged
        self::assertSame(100.0, $size->width);
    }

    public function testFitWithinLandscape(): void
    {
        $size = new Size(800.0, 600.0);
        $fitted = $size->fitWithin(new Size(400.0, 400.0));

        self::assertSame(400.0, $fitted->width);
        self::assertSame(300.0, $fitted->height);
    }

    public function testFitWithinPortrait(): void
    {
        $size = new Size(600.0, 800.0);
        $fitted = $size->fitWithin(new Size(400.0, 400.0));

        self::assertSame(300.0, $fitted->width);
        self::assertSame(400.0, $fitted->height);
    }

    public function testFitWithinAlreadySmaller(): void
    {
        $size = new Size(200.0, 100.0);
        $fitted = $size->fitWithin(new Size(400.0, 400.0));

        self::assertSame(200.0, $fitted->width);
        self::assertSame(100.0, $fitted->height);
    }

    public function testCoverLandscape(): void
    {
        $size = new Size(800.0, 600.0);
        $covered = $size->cover(new Size(400.0, 400.0));

        self::assertSame(533.0, $covered->width);
        self::assertSame(400.0, $covered->height);
    }

    public function testCoverPortrait(): void
    {
        $size = new Size(600.0, 800.0);
        $covered = $size->cover(new Size(400.0, 400.0));

        self::assertSame(400.0, $covered->width);
        self::assertSame(533.0, $covered->height);
    }
}
