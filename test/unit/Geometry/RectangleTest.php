<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Geometry;

use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rectangle::class)]
final class RectangleTest extends TestCase
{
    public function testConstruction(): void
    {
        $rect = new Rectangle(new Point(10.0, 20.0), new Size(100.0, 50.0));

        self::assertSame(10.0, $rect->origin->x);
        self::assertSame(20.0, $rect->origin->y);
        self::assertSame(100.0, $rect->size->width);
        self::assertSame(50.0, $rect->size->height);
    }

    public function testFromCoordinates(): void
    {
        $rect = Rectangle::fromCoordinates(10.0, 20.0, 110.0, 80.0);

        self::assertSame(10.0, $rect->origin->x);
        self::assertSame(20.0, $rect->origin->y);
        self::assertSame(100.0, $rect->size->width);
        self::assertSame(60.0, $rect->size->height);
    }

    public function testFromCoordinatesReversed(): void
    {
        $rect = Rectangle::fromCoordinates(110.0, 80.0, 10.0, 20.0);

        self::assertSame(10.0, $rect->origin->x);
        self::assertSame(20.0, $rect->origin->y);
        self::assertSame(100.0, $rect->size->width);
        self::assertSame(60.0, $rect->size->height);
    }

    public function testRight(): void
    {
        $rect = new Rectangle(new Point(10.0, 20.0), new Size(100.0, 50.0));

        self::assertSame(110.0, $rect->right());
    }

    public function testBottom(): void
    {
        $rect = new Rectangle(new Point(10.0, 20.0), new Size(100.0, 50.0));

        self::assertSame(70.0, $rect->bottom());
    }
}
