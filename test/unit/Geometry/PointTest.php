<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Geometry;

use Horde\Image\Geometry\Point;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Point::class)]
final class PointTest extends TestCase
{
    public function testConstruction(): void
    {
        $point = new Point(10.5, 20.3);

        self::assertSame(10.5, $point->x);
        self::assertSame(20.3, $point->y);
    }

    public function testTranslate(): void
    {
        $point = new Point(10.0, 20.0);
        $moved = $point->translate(5.0, -3.0);

        self::assertSame(15.0, $moved->x);
        self::assertSame(17.0, $moved->y);
        // Original unchanged
        self::assertSame(10.0, $point->x);
    }

    public function testTranslateZero(): void
    {
        $point = new Point(5.0, 5.0);
        $same = $point->translate(0.0, 0.0);

        self::assertSame(5.0, $same->x);
        self::assertSame(5.0, $same->y);
    }
}
