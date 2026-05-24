<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Driver\GdDriver;
use Horde\Image\Driver\GdResource;
use Horde\Image\DriverException;
use Horde\Image\Filter\Brightness;
use Horde\Image\Filter\Grayscale;
use Horde\Image\Filter\Negate;
use Horde\Image\Filter\Sepia;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\FormatException;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use GdImage;

#[CoversClass(GdDriver::class)]
#[CoversClass(GdResource::class)]
#[RequiresPhpExtension('gd')]
final class GdDriverTest extends TestCase
{
    private GdDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new GdDriver();
    }

    public function testCreate(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(1.0, 0.0, 0.0));

        self::assertInstanceOf(GdResource::class, $image);
        self::assertSame(100.0, $image->size()->width);
        self::assertSame(80.0, $image->size()->height);
    }

    public function testEncodeAsPng(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(0.0, 0.0, 1.0));
        $data = $this->driver->encode($image, ImageFormat::PNG);

        self::assertStringStartsWith("\x89PNG", $data);
    }

    public function testEncodeAsJpeg(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(0.0, 1.0, 0.0));
        $data = $this->driver->encode($image, ImageFormat::JPEG);

        self::assertStringStartsWith("\xff\xd8", $data);
    }

    public function testEncodeAsGif(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(1.0, 0.0, 1.0));
        $data = $this->driver->encode($image, ImageFormat::GIF);

        self::assertStringStartsWith('GIF', $data);
    }

    public function testLoadFromData(): void
    {
        $image = $this->driver->create(new Size(120.0, 90.0), Color::rgb(0.5, 0.5, 0.5));
        $png = $this->driver->encode($image, ImageFormat::PNG);

        $loaded = $this->driver->load($png);

        self::assertSame(120.0, $loaded->size()->width);
        self::assertSame(90.0, $loaded->size()->height);
    }

    public function testLoadFromFile(): void
    {
        $image = $this->driver->create(new Size(80.0, 60.0), Color::rgb(0.3, 0.6, 0.9));
        $png = $this->driver->encode($image, ImageFormat::PNG);
        $tmpFile = tempnam(sys_get_temp_dir(), 'gd_test_');
        self::assertNotFalse($tmpFile);
        file_put_contents($tmpFile, $png);

        try {
            $loaded = $this->driver->loadFile($tmpFile);
            self::assertSame(80.0, $loaded->size()->width);
            self::assertSame(60.0, $loaded->size()->height);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testResize(): void
    {
        $image = $this->driver->create(new Size(200.0, 100.0), Color::rgb(1.0, 1.0, 1.0));

        $resized = $image->resize(new Size(100.0, 50.0));

        self::assertSame(100.0, $resized->size()->width);
        self::assertSame(50.0, $resized->size()->height);
        self::assertNotSame($image, $resized);
    }

    public function testCrop(): void
    {
        $image = $this->driver->create(new Size(200.0, 200.0), Color::rgb(0.0, 0.0, 0.0));
        $region = new Rectangle(new Point(50.0, 50.0), new Size(100.0, 80.0));

        $cropped = $image->crop($region);

        self::assertSame(100.0, $cropped->size()->width);
        self::assertSame(80.0, $cropped->size()->height);
    }

    public function testFlipHorizontal(): void
    {
        $image = $this->driver->create(new Size(100.0, 100.0), Color::rgb(1.0, 0.0, 0.0));

        $flipped = $image->flip(horizontal: true);

        self::assertSame(100.0, $flipped->size()->width);
        self::assertNotSame($image, $flipped);
    }

    public function testFlipVertical(): void
    {
        $image = $this->driver->create(new Size(100.0, 100.0), Color::rgb(0.0, 1.0, 0.0));

        $flipped = $image->flip(vertical: true);

        self::assertSame(100.0, $flipped->size()->width);
    }

    public function testRotate(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(1.0, 1.0, 0.0));

        $rotated = $image->rotate(90.0, Color::rgb(0.0, 0.0, 0.0));

        self::assertNotSame($image, $rotated);
    }

    public function testGrayscaleFilter(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(1.0, 0.0, 0.0));

        $gray = $image->apply(new Grayscale());

        self::assertSame(50.0, $gray->size()->width);
    }

    public function testNegateFilter(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(1.0, 1.0, 1.0));

        $neg = $image->apply(new Negate());

        self::assertSame(50.0, $neg->size()->width);
    }

    public function testSepiaFilter(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(0.5, 0.5, 0.5));

        $sepia = $image->apply(new Sepia(80.0));

        self::assertSame(50.0, $sepia->size()->width);
    }

    public function testBrightnessFilter(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(0.5, 0.5, 0.5));

        $bright = $image->apply(new Brightness(20.0));

        self::assertSame(50.0, $bright->size()->width);
    }

    public function testSupportsFormats(): void
    {
        self::assertTrue($this->driver->supports(ImageFormat::PNG));
        self::assertTrue($this->driver->supports(ImageFormat::JPEG));
        self::assertTrue($this->driver->supports(ImageFormat::GIF));
        self::assertFalse($this->driver->supports(ImageFormat::SVG));
        self::assertFalse($this->driver->supports(ImageFormat::TIFF));
    }

    public function testDrawingContextThrows(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(0.0, 0.0, 0.0));

        $this->expectException(DriverException::class);
        $image->drawingContext();
    }

    public function testImmutability(): void
    {
        $image = $this->driver->create(new Size(200.0, 100.0), Color::rgb(1.0, 1.0, 1.0));
        $resized = $image->resize(new Size(50.0, 25.0));

        self::assertSame(200.0, $image->size()->width);
        self::assertSame(50.0, $resized->size()->width);
    }

    public function testGdResourceAccessor(): void
    {
        $image = $this->driver->create(new Size(30.0, 20.0), Color::rgb(0.0, 0.0, 0.0));

        self::assertInstanceOf(GdResource::class, $image);
        self::assertInstanceOf(GdImage::class, $image->gd());
    }

    public function testFlipBoth(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(1.0, 0.0, 0.0));

        $flipped = $image->flip(horizontal: true, vertical: true);

        self::assertSame(100.0, $flipped->size()->width);
        self::assertSame(80.0, $flipped->size()->height);
        self::assertNotSame($image, $flipped);
    }

    public function testFlipNone(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(0.0, 1.0, 0.0));

        $same = $image->flip(horizontal: false, vertical: false);

        self::assertSame(100.0, $same->size()->width);
        self::assertNotSame($image, $same);
    }

    public function testEncodeBmp(): void
    {
        $image = $this->driver->create(new Size(20.0, 20.0), Color::rgb(1.0, 1.0, 0.0));
        $data = $this->driver->encode($image, ImageFormat::BMP);

        self::assertStringStartsWith('BM', $data);
    }

    public function testUnsupportedFormatThrows(): void
    {
        $image = $this->driver->create(new Size(20.0, 20.0), Color::rgb(0.0, 0.0, 0.0));

        $this->expectException(FormatException::class);
        $this->driver->encode($image, ImageFormat::SVG);
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
}
