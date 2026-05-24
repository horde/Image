<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\GdDriver;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Horde\Image\Effect\PhotoStack;
use Horde\Image\Effect\PhotoStackStyle;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhotoStack::class)]
#[CoversClass(PhotoStackStyle::class)]
#[RequiresPhpExtension('imagick')]
final class PhotoStackTest extends TestCase
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
        $effect = new PhotoStack();

        self::assertSame([], $effect->images);
        self::assertSame(PhotoStackStyle::Plain, $effect->style);
        self::assertSame(150, $effect->thumbnailHeight);
        self::assertSame(1, $effect->borderWidth);
        self::assertSame(5, $effect->offset);
        self::assertSame(0, $effect->padding);
        self::assertSame(10, $effect->borderRounding);
    }

    public function testPhotoStackStyleEnumValues(): void
    {
        self::assertSame('plain', PhotoStackStyle::Plain->value);
        self::assertSame('rounded', PhotoStackStyle::Rounded->value);
        self::assertSame('polaroid', PhotoStackStyle::Polaroid->value);
        self::assertCount(3, PhotoStackStyle::cases());
    }

    public function testApplyReturnsNewInstance(): void
    {
        $image = $this->createImage();
        $effect = new PhotoStack();

        $result = $effect->apply($image);

        self::assertNotSame($image, $result);
    }

    #[DataProvider('styleProvider')]
    public function testAllStylesApplySuccessfully(PhotoStackStyle $style): void
    {
        $top = $this->createImage(200.0, 150.0);
        $bg1 = $this->createImage(180.0, 120.0);
        $bg2 = $this->createImage(160.0, 100.0);

        $effect = new PhotoStack(
            images: [$bg1, $bg2],
            style: $style,
        );

        $result = $effect->apply($top);

        self::assertInstanceOf(ImagickResource::class, $result);
    }

    public static function styleProvider(): array
    {
        $cases = [];
        foreach (PhotoStackStyle::cases() as $style) {
            $cases[$style->value] = [$style];
        }
        return $cases;
    }

    public function testPlainStyleProducesOutput(): void
    {
        $top = $this->createImage();
        $bg = $this->createImage(180.0, 120.0);

        $effect = new PhotoStack(
            images: [$bg],
            style: PhotoStackStyle::Plain,
        );

        $result = $effect->apply($top);
        $geo = $result->imagick()->getImageGeometry();

        self::assertGreaterThan(0, $geo['width']);
        self::assertGreaterThan(0, $geo['height']);
    }

    public function testRoundedStyleProducesOutput(): void
    {
        $top = $this->createImage();
        $bg = $this->createImage(180.0, 120.0);

        $effect = new PhotoStack(
            images: [$bg],
            style: PhotoStackStyle::Rounded,
            borderRounding: 15,
        );

        $result = $effect->apply($top);
        $geo = $result->imagick()->getImageGeometry();

        self::assertGreaterThan(0, $geo['width']);
        self::assertGreaterThan(0, $geo['height']);
    }

    public function testPolaroidStyleProducesOutput(): void
    {
        $top = $this->createImage();
        $bg = $this->createImage(180.0, 120.0);

        $effect = new PhotoStack(
            images: [$bg],
            style: PhotoStackStyle::Polaroid,
        );

        $result = $effect->apply($top);
        $geo = $result->imagick()->getImageGeometry();

        self::assertGreaterThan(0, $geo['width']);
        self::assertGreaterThan(0, $geo['height']);
    }

    public function testSingleImageNoBackground(): void
    {
        $top = $this->createImage();

        $effect = new PhotoStack(style: PhotoStackStyle::Plain);

        $result = $effect->apply($top);

        self::assertInstanceOf(ImagickResource::class, $result);
    }

    public function testWithPadding(): void
    {
        $top = $this->createImage();
        $effect = new PhotoStack(padding: 20);

        $result = $effect->apply($top);
        $geo = $result->imagick()->getImageGeometry();

        self::assertGreaterThan(0, $geo['width']);
    }

    public function testEffectInterfaceViaImageResource(): void
    {
        $image = $this->createImage();
        $effect = new PhotoStack(style: PhotoStackStyle::Plain);

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
        $effect = new PhotoStack();

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('PhotoStack effect requires ImagickResource');
        $effect->apply($gdImage);
    }

    public function testCustomColors(): void
    {
        $effect = new PhotoStack(
            background: Color::rgb(1.0, 1.0, 1.0),
            borderColor: Color::rgb(0.0, 0.0, 0.0),
        );

        self::assertSame(1.0, $effect->background()->red());
        self::assertSame(0.0, $effect->borderColor()->red());
    }
}
