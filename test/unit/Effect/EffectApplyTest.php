<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\Effect\Border;
use Horde\Image\Effect\CenterCrop;
use Horde\Image\Effect\Composite;
use Horde\Image\Effect\DropShadow;
use Horde\Image\Effect\LiquidResize;
use Horde\Image\Effect\RoundCorners;
use Horde\Image\Effect\SmartCrop;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(Border::class)]
#[CoversClass(CenterCrop::class)]
#[CoversClass(Composite::class)]
#[CoversClass(DropShadow::class)]
#[CoversClass(LiquidResize::class)]
#[CoversClass(RoundCorners::class)]
#[CoversClass(SmartCrop::class)]
#[CoversClass(ImagickResource::class)]
#[RequiresPhpExtension('imagick')]
final class EffectApplyTest extends TestCase
{
    private ImagickDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ImagickDriver();
    }

    private function createImage(float $width = 200.0, float $height = 150.0): ImagickResource
    {
        return $this->driver->create(new Size($width, $height), Color::rgb(0.5, 0.5, 0.5));
    }

    public function testBorderAddsDimensions(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $result = $image->effect(new Border(width: 5));

        self::assertSame(110.0, $result->size()->width);
        self::assertSame(90.0, $result->size()->height);
    }

    public function testBorderWithoutPreserveTransparency(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $result = $image->effect(new Border(width: 5, preserveTransparency: false));

        self::assertSame(110.0, $result->size()->width);
        self::assertSame(90.0, $result->size()->height);
    }

    public function testBorderImmutability(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $image->effect(new Border(width: 10));

        self::assertSame(100.0, $image->size()->width);
        self::assertSame(80.0, $image->size()->height);
    }

    public function testCenterCropReducesDimensions(): void
    {
        $image = $this->createImage(200.0, 150.0);
        $result = $image->effect(new CenterCrop(width: 100, height: 80));

        self::assertSame(100.0, $result->size()->width);
        self::assertSame(80.0, $result->size()->height);
    }

    public function testCenterCropImmutability(): void
    {
        $image = $this->createImage(200.0, 150.0);
        $image->effect(new CenterCrop(width: 50, height: 50));

        self::assertSame(200.0, $image->size()->width);
        self::assertSame(150.0, $image->size()->height);
    }

    public function testCompositeOverlay(): void
    {
        $base = $this->createImage(200.0, 150.0);
        $overlay = $this->createImage(50.0, 50.0);

        $result = $base->effect(new Composite(overlay: $overlay, x: 10, y: 10));

        self::assertSame(200.0, $result->size()->width);
        self::assertSame(150.0, $result->size()->height);
    }

    public function testCompositeCentersWhenNoPosition(): void
    {
        $base = $this->createImage(200.0, 150.0);
        $overlay = $this->createImage(50.0, 50.0);

        $result = $base->effect(new Composite(overlay: $overlay));

        self::assertSame(200.0, $result->size()->width);
        self::assertSame(150.0, $result->size()->height);
    }

    public function testCompositeImmutability(): void
    {
        $base = $this->createImage(200.0, 150.0);
        $overlay = $this->createImage(50.0, 50.0);
        $base->effect(new Composite(overlay: $overlay, x: 0, y: 0));

        self::assertSame(200.0, $base->size()->width);
    }

    public function testDropShadowExpandsDimensions(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $result = $image->effect(new DropShadow(distance: 5, sigma: 3.0));

        self::assertGreaterThan(100.0, $result->size()->width);
        self::assertGreaterThan(80.0, $result->size()->height);
    }

    public function testDropShadowWithPadding(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $noPad = $image->effect(new DropShadow(distance: 5, sigma: 3.0, padding: 0));
        $withPad = $image->effect(new DropShadow(distance: 5, sigma: 3.0, padding: 10));

        self::assertGreaterThan($noPad->size()->width, $withPad->size()->width);
    }

    public function testDropShadowImmutability(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $image->effect(new DropShadow());

        self::assertSame(100.0, $image->size()->width);
        self::assertSame(80.0, $image->size()->height);
    }

    public function testLiquidResizeChangesSize(): void
    {
        $image = $this->createImage(200.0, 150.0);
        $result = $image->effect(new LiquidResize(width: 100, height: 100));

        self::assertSame(100.0, $result->size()->width);
        self::assertSame(100.0, $result->size()->height);
    }

    public function testLiquidResizeImmutability(): void
    {
        $image = $this->createImage(200.0, 150.0);
        $image->effect(new LiquidResize(width: 50, height: 50));

        self::assertSame(200.0, $image->size()->width);
        self::assertSame(150.0, $image->size()->height);
    }

    public function testRoundCornersPreservesSize(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $result = $image->effect(new RoundCorners(radius: 15));

        self::assertSame(100.0, $result->size()->width);
        self::assertSame(80.0, $result->size()->height);
    }

    public function testRoundCornersWithBorderExpandsSize(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $borderColor = Color::rgba(0.0, 0.0, 0.0, 1.0);
        $result = $image->effect(new RoundCorners(radius: 10, border: 6, borderColor: $borderColor));

        self::assertSame(106.0, $result->size()->width);
        self::assertSame(86.0, $result->size()->height);
    }

    public function testRoundCornersImmutability(): void
    {
        $image = $this->createImage(100.0, 80.0);
        $image->effect(new RoundCorners(radius: 20));

        self::assertSame(100.0, $image->size()->width);
        self::assertSame(80.0, $image->size()->height);
    }

    public function testSmartCropProducesCroppedSize(): void
    {
        $image = $this->createImage(200.0, 150.0);
        $result = $image->effect(new SmartCrop(width: 80, height: 60));

        self::assertSame(80.0, $result->size()->width);
        self::assertSame(60.0, $result->size()->height);
    }

    public function testSmartCropSkipsWhenSmallerThanTarget(): void
    {
        $image = $this->createImage(50.0, 40.0);
        $result = $image->effect(new SmartCrop(width: 100, height: 100));

        self::assertSame(50.0, $result->size()->width);
        self::assertSame(40.0, $result->size()->height);
    }

    public function testSmartCropImmutability(): void
    {
        $image = $this->createImage(200.0, 150.0);
        $image->effect(new SmartCrop(width: 80, height: 60));

        self::assertSame(200.0, $image->size()->width);
        self::assertSame(150.0, $image->size()->height);
    }

    public function testEffectChaining(): void
    {
        $image = $this->createImage(200.0, 150.0);
        $result = $image
            ->effect(new CenterCrop(width: 100, height: 100))
            ->effect(new Border(width: 5))
            ->effect(new RoundCorners(radius: 10));

        self::assertSame(110.0, $result->size()->width);
        self::assertSame(110.0, $result->size()->height);
    }

    public function testEffectViaApplyDirectly(): void
    {
        $image = $this->createImage(200.0, 150.0);
        $effect = new CenterCrop(width: 80, height: 60);
        $result = $effect->apply($image);

        self::assertSame(80.0, $result->size()->width);
        self::assertSame(60.0, $result->size()->height);
    }
}
