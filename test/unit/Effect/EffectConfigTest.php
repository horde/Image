<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Effect\Border;
use Horde\Image\Effect\CenterCrop;
use Horde\Image\Effect\Composite;
use Horde\Image\Effect\DropShadow;
use Horde\Image\Effect\Effect;
use Horde\Image\Effect\LiquidResize;
use Horde\Image\Effect\RoundCorners;
use Horde\Image\Effect\SmartCrop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Border::class)]
#[CoversClass(CenterCrop::class)]
#[CoversClass(DropShadow::class)]
#[CoversClass(LiquidResize::class)]
#[CoversClass(RoundCorners::class)]
#[CoversClass(SmartCrop::class)]
final class EffectConfigTest extends TestCase
{
    public function testAllImplementEffectInterface(): void
    {
        $effects = [
            new Border(),
            new CenterCrop(width: 100, height: 100),
            new DropShadow(),
            new LiquidResize(width: 200, height: 150),
            new RoundCorners(),
            new SmartCrop(width: 100, height: 100),
        ];

        foreach ($effects as $effect) {
            self::assertInstanceOf(Effect::class, $effect);
        }
    }

    public function testBorderDefaults(): void
    {
        $effect = new Border();

        self::assertSame(1, $effect->width);
        self::assertTrue($effect->preserveTransparency);
        self::assertEqualsWithDelta(0.0, $effect->color()->red(), 0.001);
        self::assertEqualsWithDelta(0.0, $effect->color()->green(), 0.001);
        self::assertEqualsWithDelta(0.0, $effect->color()->blue(), 0.001);
    }

    public function testBorderCustom(): void
    {
        $color = Color::rgb(1.0, 0.0, 0.0);
        $effect = new Border(color: $color, width: 5, preserveTransparency: false);

        self::assertSame(5, $effect->width);
        self::assertFalse($effect->preserveTransparency);
        self::assertEqualsWithDelta(1.0, $effect->color()->red(), 0.001);
    }

    public function testCenterCrop(): void
    {
        $effect = new CenterCrop(width: 200, height: 150);

        self::assertSame(200, $effect->width);
        self::assertSame(150, $effect->height);
    }

    public function testDropShadowDefaults(): void
    {
        $effect = new DropShadow();

        self::assertSame(5, $effect->distance);
        self::assertSame(3.0, $effect->sigma);
        self::assertSame(0, $effect->padding);
    }

    public function testDropShadowCustom(): void
    {
        $bg = Color::rgba(1.0, 1.0, 1.0, 1.0);
        $effect = new DropShadow(distance: 10, sigma: 5.0, padding: 8, background: $bg);

        self::assertSame(10, $effect->distance);
        self::assertSame(5.0, $effect->sigma);
        self::assertSame(8, $effect->padding);
    }

    public function testLiquidResize(): void
    {
        $effect = new LiquidResize(width: 300, height: 200);

        self::assertSame(300, $effect->width);
        self::assertSame(200, $effect->height);
        self::assertSame(0.0, $effect->deltaX);
        self::assertSame(0.0, $effect->rigidity);
    }

    public function testLiquidResizeCustom(): void
    {
        $effect = new LiquidResize(width: 300, height: 200, deltaX: 1.5, rigidity: 2.0);

        self::assertSame(1.5, $effect->deltaX);
        self::assertSame(2.0, $effect->rigidity);
    }

    public function testRoundCornersDefaults(): void
    {
        $effect = new RoundCorners();

        self::assertSame(10, $effect->radius);
        self::assertSame(0, $effect->border);
    }

    public function testRoundCornersCustom(): void
    {
        $bg = Color::rgba(1.0, 1.0, 1.0, 1.0);
        $borderColor = Color::rgb(0.0, 0.0, 0.0);
        $effect = new RoundCorners(radius: 20, background: $bg, border: 3, borderColor: $borderColor);

        self::assertSame(20, $effect->radius);
        self::assertSame(3, $effect->border);
    }

    public function testSmartCrop(): void
    {
        $effect = new SmartCrop(width: 80, height: 60);

        self::assertSame(80, $effect->width);
        self::assertSame(60, $effect->height);
    }
}
