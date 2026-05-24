<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\GdDriver;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Horde\Image\Effect\PolaroidImage;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(PolaroidImage::class)]
#[RequiresPhpExtension('imagick')]
final class PolaroidImageTest extends TestCase
{
    private ImagickDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ImagickDriver();
    }

    private function createImage(float $width = 200.0, float $height = 150.0): ImagickResource
    {
        return $this->driver->create(new Size($width, $height), Color::rgb(0.4, 0.6, 0.8));
    }

    public function testDefaultConstruction(): void
    {
        $effect = new PolaroidImage();

        self::assertSame(0.0, $effect->angle);
        self::assertSame(0.0, $effect->background()->red());
        self::assertSame(0.0, $effect->background()->alpha());
        self::assertSame(0.0, $effect->shadowColor()->red());
    }

    public function testCustomConstruction(): void
    {
        $bg = Color::rgb(1.0, 1.0, 1.0);
        $shadow = Color::rgb(0.3, 0.3, 0.3);
        $effect = new PolaroidImage(angle: -5.0, background: $bg, shadowColor: $shadow);

        self::assertSame(-5.0, $effect->angle);
        self::assertSame(1.0, $effect->background()->red());
        self::assertSame(0.3, $effect->shadowColor()->red());
    }

    public function testApplyReturnsNewInstance(): void
    {
        $image = $this->createImage();
        $effect = new PolaroidImage();

        $result = $effect->apply($image);

        self::assertNotSame($image, $result);
    }

    public function testApplyModifiesImageData(): void
    {
        $image = $this->createImage();
        $effect = new PolaroidImage(angle: 5.0);

        $result = $effect->apply($image);

        $originalBlob = $image->imagick()->getImageBlob();
        $resultBlob = $result->imagick()->getImageBlob();
        self::assertNotSame($originalBlob, $resultBlob);
    }

    public function testApplyWithAngleProducesDifferentResult(): void
    {
        $image = $this->createImage();
        $noAngle = new PolaroidImage(angle: 0.0);
        $withAngle = new PolaroidImage(angle: 15.0);

        $result1 = $noAngle->apply($image);
        $result2 = $withAngle->apply($image);

        $blob1 = $result1->imagick()->getImageBlob();
        $blob2 = $result2->imagick()->getImageBlob();
        self::assertNotSame($blob1, $blob2);
    }

    public function testApplyWithWhiteBackground(): void
    {
        $image = $this->createImage(100.0, 100.0);
        $effect = new PolaroidImage(
            background: Color::rgb(1.0, 1.0, 1.0),
            shadowColor: Color::rgb(0.0, 0.0, 0.0),
        );

        $result = $effect->apply($image);

        self::assertInstanceOf(ImagickResource::class, $result);
    }

    public function testEffectInterfaceViaImageResource(): void
    {
        $image = $this->createImage();
        $effect = new PolaroidImage(angle: -3.0);

        $result = $image->effect($effect);

        self::assertInstanceOf(ImagickResource::class, $result);
        self::assertNotSame($image, $result);
    }

    public function testApplyOnNonImagickResourceThrows(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd required');
        }

        $gdDriver = new GdDriver();
        $gdImage = $gdDriver->create(new Size(50.0, 50.0), Color::rgb(0.5, 0.5, 0.5));
        $effect = new PolaroidImage();

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('PolaroidImage effect requires ImagickResource');
        $effect->apply($gdImage);
    }
}
