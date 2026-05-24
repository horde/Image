<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Driver\PngDriver;
use Horde\Image\Driver\PngResource;
use Horde\Image\DriverException;
use Horde\Image\Format\ImageFormat;
use Horde\Image\FormatException;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PngDriver::class)]
#[CoversClass(PngResource::class)]
final class PngDriverTest extends TestCase
{
    private PngDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new PngDriver();
    }

    public function testCreate(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(1.0, 0.0, 0.0));

        self::assertInstanceOf(PngResource::class, $image);
        self::assertSame(100.0, $image->size()->width);
        self::assertSame(80.0, $image->size()->height);
    }

    public function testCreateFillsBackground(): void
    {
        $image = $this->driver->create(new Size(10.0, 10.0), Color::rgb(1.0, 0.0, 0.0));
        self::assertInstanceOf(PngResource::class, $image);

        $pixel = $image->getPixel(5, 5);
        self::assertSame([255, 0, 0], $pixel);
    }

    public function testEncodeProducesValidPng(): void
    {
        $image = $this->driver->create(new Size(4.0, 4.0), Color::rgb(0.0, 0.0, 1.0));
        $data = $this->driver->encode($image, ImageFormat::PNG);

        self::assertStringStartsWith("\x89PNG", $data);

        $gd = @imagecreatefromstring($data);
        self::assertNotFalse($gd, 'Encoded PNG must be loadable by GD');
        self::assertSame(4, imagesx($gd));
        self::assertSame(4, imagesy($gd));
        imagedestroy($gd);
    }

    public function testEncodePreservesPixelColors(): void
    {
        $image = $this->driver->create(new Size(2.0, 2.0), Color::rgb(0.0, 1.0, 0.0));
        $data = $this->driver->encode($image, ImageFormat::PNG);

        $gd = imagecreatefromstring($data);
        self::assertNotFalse($gd);
        $rgb = imagecolorat($gd, 0, 0);
        self::assertNotFalse($rgb);
        self::assertSame(0, ($rgb >> 16) & 0xFF);
        self::assertSame(255, ($rgb >> 8) & 0xFF);
        self::assertSame(0, $rgb & 0xFF);
        imagedestroy($gd);
    }

    public function testLoadFromData(): void
    {
        $gd = imagecreatetruecolor(30, 20);
        self::assertNotFalse($gd);
        $red = imagecolorallocate($gd, 255, 0, 0);
        self::assertNotFalse($red);
        imagefilledrectangle($gd, 0, 0, 29, 19, $red);
        ob_start();
        imagepng($gd);
        $png = ob_get_clean();
        self::assertNotFalse($png);
        imagedestroy($gd);

        $loaded = $this->driver->load($png);
        self::assertInstanceOf(PngResource::class, $loaded);
        self::assertSame(30.0, $loaded->size()->width);
        self::assertSame(20.0, $loaded->size()->height);

        $pixel = $loaded->getPixel(15, 10);
        self::assertSame([255, 0, 0], $pixel);
    }

    public function testLoadInvalidDataThrows(): void
    {
        $this->expectException(DriverException::class);
        $this->driver->load('not an image');
    }

    public function testLoadFileNotReadableThrows(): void
    {
        $this->expectException(DriverException::class);
        $this->driver->loadFile('/nonexistent/path/file.png');
    }

    public function testResize(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(0.5, 0.5, 0.5));

        $resized = $image->resize(new Size(50.0, 25.0));

        self::assertSame(50.0, $resized->size()->width);
        self::assertSame(25.0, $resized->size()->height);
        self::assertNotSame($image, $resized);
    }

    public function testCrop(): void
    {
        $image = $this->driver->create(new Size(100.0, 100.0), Color::rgb(0.0, 0.0, 0.0));
        self::assertInstanceOf(PngResource::class, $image);
        $image->setPixel(60, 60, 255, 0, 0);

        $cropped = $image->crop(new Rectangle(new Point(50.0, 50.0), new Size(20.0, 20.0)));
        self::assertInstanceOf(PngResource::class, $cropped);

        self::assertSame(20.0, $cropped->size()->width);
        self::assertSame(20.0, $cropped->size()->height);
        self::assertSame([255, 0, 0], $cropped->getPixel(10, 10));
    }

    public function testRotate90(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(1.0, 1.0, 1.0));

        $rotated = $image->rotate(90.0, Color::rgb(0.0, 0.0, 0.0));

        self::assertSame(50.0, $rotated->size()->width);
        self::assertSame(100.0, $rotated->size()->height);
    }

    public function testRotate180(): void
    {
        $image = $this->driver->create(new Size(4.0, 4.0), Color::rgb(0.0, 0.0, 0.0));
        self::assertInstanceOf(PngResource::class, $image);
        $image->setPixel(0, 0, 255, 0, 0);

        $rotated = $image->rotate(180.0, Color::rgb(0.0, 0.0, 0.0));
        self::assertInstanceOf(PngResource::class, $rotated);

        self::assertSame([255, 0, 0], $rotated->getPixel(3, 3));
    }

    public function testFlipHorizontal(): void
    {
        $image = $this->driver->create(new Size(4.0, 4.0), Color::rgb(0.0, 0.0, 0.0));
        self::assertInstanceOf(PngResource::class, $image);
        $image->setPixel(0, 0, 255, 0, 0);

        $flipped = $image->flip(horizontal: true);
        self::assertInstanceOf(PngResource::class, $flipped);

        self::assertSame([255, 0, 0], $flipped->getPixel(3, 0));
    }

    public function testFlipVertical(): void
    {
        $image = $this->driver->create(new Size(4.0, 4.0), Color::rgb(0.0, 0.0, 0.0));
        self::assertInstanceOf(PngResource::class, $image);
        $image->setPixel(0, 0, 255, 0, 0);

        $flipped = $image->flip(vertical: true);
        self::assertInstanceOf(PngResource::class, $flipped);

        self::assertSame([255, 0, 0], $flipped->getPixel(0, 3));
    }

    public function testSupportsOnlyPng(): void
    {
        self::assertTrue($this->driver->supports(ImageFormat::PNG));
        self::assertFalse($this->driver->supports(ImageFormat::JPEG));
        self::assertFalse($this->driver->supports(ImageFormat::GIF));
        self::assertFalse($this->driver->supports(ImageFormat::SVG));
    }

    public function testUnsupportedFormatThrows(): void
    {
        $image = $this->driver->create(new Size(10.0, 10.0), Color::rgb(0.0, 0.0, 0.0));

        $this->expectException(FormatException::class);
        $this->driver->encode($image, ImageFormat::JPEG);
    }

    public function testDrawingContextThrows(): void
    {
        $image = $this->driver->create(new Size(10.0, 10.0), Color::rgb(0.0, 0.0, 0.0));

        $this->expectException(DriverException::class);
        $image->drawingContext();
    }

    public function testImmutability(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(1.0, 1.0, 1.0));
        $resized = $image->resize(new Size(50.0, 25.0));

        self::assertSame(100.0, $image->size()->width);
        self::assertSame(50.0, $resized->size()->width);
    }

    public function testRoundTripThroughEncodeDecode(): void
    {
        $image = $this->driver->create(new Size(8.0, 8.0), Color::rgb(0.2, 0.4, 0.6));
        self::assertInstanceOf(PngResource::class, $image);

        $encoded = $this->driver->encode($image, ImageFormat::PNG);
        $decoded = $this->driver->load($encoded);
        self::assertInstanceOf(PngResource::class, $decoded);

        self::assertSame(8.0, $decoded->size()->width);
        self::assertSame(8.0, $decoded->size()->height);
        self::assertSame($image->getPixel(0, 0), $decoded->getPixel(0, 0));
    }
}
