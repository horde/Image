<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Drawing;

use Horde\Image\Drawing\FontSize;
use Horde\Image\Drawing\TextStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FontSize::class)]
final class FontSizeTest extends TestCase
{
    public function testPointValues(): void
    {
        self::assertSame(8.0, FontSize::Tiny->points());
        self::assertSame(12.0, FontSize::Small->points());
        self::assertSame(18.0, FontSize::Medium->points());
        self::assertSame(24.0, FontSize::Large->points());
        self::assertSame(30.0, FontSize::Giant->points());
    }

    public function testFromPointsExactMatch(): void
    {
        self::assertSame(FontSize::Medium, FontSize::fromPoints(18.0));
        self::assertSame(FontSize::Tiny, FontSize::fromPoints(8.0));
    }

    public function testFromPointsNearestMatch(): void
    {
        self::assertSame(FontSize::Small, FontSize::fromPoints(11.0));
        self::assertSame(FontSize::Medium, FontSize::fromPoints(16.0));
        self::assertSame(FontSize::Large, FontSize::fromPoints(22.0));
        self::assertSame(FontSize::Giant, FontSize::fromPoints(28.0));
    }

    public function testFromPointsEdgeCase(): void
    {
        self::assertSame(FontSize::Tiny, FontSize::fromPoints(1.0));
        self::assertSame(FontSize::Giant, FontSize::fromPoints(100.0));
    }

    public function testNextUp(): void
    {
        self::assertSame(FontSize::Small, FontSize::nextUp(8.0));
        self::assertSame(FontSize::Medium, FontSize::nextUp(12.0));
        self::assertSame(FontSize::Large, FontSize::nextUp(18.0));
        self::assertSame(FontSize::Giant, FontSize::nextUp(24.0));
        self::assertNull(FontSize::nextUp(30.0));
        self::assertNull(FontSize::nextUp(50.0));
    }

    public function testNextDown(): void
    {
        self::assertNull(FontSize::nextDown(8.0));
        self::assertNull(FontSize::nextDown(1.0));
        self::assertSame(FontSize::Tiny, FontSize::nextDown(12.0));
        self::assertSame(FontSize::Small, FontSize::nextDown(18.0));
        self::assertSame(FontSize::Medium, FontSize::nextDown(24.0));
        self::assertSame(FontSize::Large, FontSize::nextDown(30.0));
    }

    public function testNextUpFromBetweenValues(): void
    {
        self::assertSame(FontSize::Medium, FontSize::nextUp(15.0));
        self::assertSame(FontSize::Tiny, FontSize::nextUp(5.0));
    }

    public function testNextDownFromBetweenValues(): void
    {
        self::assertSame(FontSize::Small, FontSize::nextDown(15.0));
        self::assertSame(FontSize::Large, FontSize::nextDown(25.0));
    }

    public function testTextStyleAcceptsFontSize(): void
    {
        $style = new TextStyle(size: FontSize::Large);
        self::assertSame(24.0, $style->size);
    }

    public function testTextStyleAcceptsFloat(): void
    {
        $style = new TextStyle(size: 14.0);
        self::assertSame(14.0, $style->size);
    }

    public function testStringValues(): void
    {
        self::assertSame('tiny', FontSize::Tiny->value);
        self::assertSame('giant', FontSize::Giant->value);
    }
}
