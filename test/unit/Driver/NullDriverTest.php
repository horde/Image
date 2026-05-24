<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Driver\NullDriver;
use Horde\Image\Driver\NullResource;
use Horde\Image\DriverException;
use Horde\Image\Filter\Grayscale;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NullDriver::class)]
#[CoversClass(NullResource::class)]
final class NullDriverTest extends TestCase
{
    private NullDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new NullDriver();
    }

    public function testCreate(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(1.0, 0.0, 0.0));

        self::assertInstanceOf(NullResource::class, $image);
        self::assertSame(100.0, $image->size()->width);
        self::assertSame(80.0, $image->size()->height);
    }

    public function testEncodeReturnsEmptyString(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(0.0, 0.0, 1.0));
        $data = $this->driver->encode($image, ImageFormat::PNG);

        self::assertSame('', $data);
    }

    public function testLoadDetectsSize(): void
    {
        $gd = imagecreatetruecolor(120, 90);
        self::assertNotFalse($gd);
        ob_start();
        imagepng($gd);
        $png = ob_get_clean();
        self::assertNotFalse($png);
        imagedestroy($gd);

        $loaded = $this->driver->load($png);

        self::assertSame(120.0, $loaded->size()->width);
        self::assertSame(90.0, $loaded->size()->height);
    }

    public function testLoadInvalidDataReturnsFallbackSize(): void
    {
        $loaded = $this->driver->load('not image data');

        self::assertSame(1.0, $loaded->size()->width);
        self::assertSame(1.0, $loaded->size()->height);
    }

    public function testLoadFileDetectsSize(): void
    {
        $gd = imagecreatetruecolor(80, 60);
        self::assertNotFalse($gd);
        $tmp = tempnam(sys_get_temp_dir(), 'null_test_');
        self::assertNotFalse($tmp);
        imagepng($gd, $tmp);
        imagedestroy($gd);

        try {
            $loaded = $this->driver->loadFile($tmp);
            self::assertSame(80.0, $loaded->size()->width);
            self::assertSame(60.0, $loaded->size()->height);
        } finally {
            @unlink($tmp);
        }
    }

    public function testLoadFileNotReadableThrows(): void
    {
        $this->expectException(DriverException::class);
        $this->driver->loadFile('/nonexistent/path/file.png');
    }

    public function testResize(): void
    {
        $image = $this->driver->create(new Size(200.0, 100.0), Color::rgb(1.0, 1.0, 1.0));

        $resized = $image->resize(new Size(50.0, 25.0));

        self::assertSame(50.0, $resized->size()->width);
        self::assertSame(25.0, $resized->size()->height);
        self::assertNotSame($image, $resized);
    }

    public function testCrop(): void
    {
        $image = $this->driver->create(new Size(200.0, 200.0), Color::rgb(0.0, 0.0, 0.0));
        $region = new Rectangle(new Point(10.0, 10.0), new Size(100.0, 80.0));

        $cropped = $image->crop($region);

        self::assertSame(100.0, $cropped->size()->width);
        self::assertSame(80.0, $cropped->size()->height);
    }

    public function testRotate90SwapsDimensions(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(1.0, 0.0, 0.0));

        $rotated = $image->rotate(90.0, Color::rgb(0.0, 0.0, 0.0));

        self::assertSame(50.0, $rotated->size()->width);
        self::assertSame(100.0, $rotated->size()->height);
    }

    public function testRotate270SwapsDimensions(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(1.0, 0.0, 0.0));

        $rotated = $image->rotate(270.0, Color::rgb(0.0, 0.0, 0.0));

        self::assertSame(50.0, $rotated->size()->width);
        self::assertSame(100.0, $rotated->size()->height);
    }

    public function testRotateOtherPreservesSize(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(1.0, 0.0, 0.0));

        $rotated = $image->rotate(45.0, Color::rgb(0.0, 0.0, 0.0));

        self::assertSame(100.0, $rotated->size()->width);
        self::assertSame(50.0, $rotated->size()->height);
    }

    public function testFlip(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(0.0, 1.0, 0.0));

        $flipped = $image->flip(horizontal: true);

        self::assertSame(100.0, $flipped->size()->width);
        self::assertSame(80.0, $flipped->size()->height);
        self::assertNotSame($image, $flipped);
    }

    public function testApplyFilterNoOp(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(0.5, 0.5, 0.5));

        $result = $image->apply(new Grayscale());

        self::assertSame(50.0, $result->size()->width);
        self::assertNotSame($image, $result);
    }

    public function testSupportsFormats(): void
    {
        self::assertTrue($this->driver->supports(ImageFormat::PNG));
        self::assertTrue($this->driver->supports(ImageFormat::JPEG));
        self::assertTrue($this->driver->supports(ImageFormat::GIF));
        self::assertTrue($this->driver->supports(ImageFormat::WebP));
        self::assertTrue($this->driver->supports(ImageFormat::TIFF));
        self::assertTrue($this->driver->supports(ImageFormat::BMP));
        self::assertTrue($this->driver->supports(ImageFormat::AVIF));
        self::assertFalse($this->driver->supports(ImageFormat::SVG));
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
}
