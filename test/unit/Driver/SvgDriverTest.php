<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Driver\SvgDrawingContext;
use Horde\Image\Driver\SvgDriver;
use Horde\Image\Driver\SvgResource;
use Horde\Image\DriverException;
use Horde\Image\Format\ImageFormat;
use Horde\Image\FormatException;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SvgDriver::class)]
#[CoversClass(SvgResource::class)]
#[CoversClass(SvgDrawingContext::class)]
final class SvgDriverTest extends TestCase
{
    private SvgDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new SvgDriver();
    }

    public function testCreate(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(1.0, 0.0, 0.0));

        self::assertInstanceOf(SvgResource::class, $image);
        self::assertSame(100.0, $image->size()->width);
        self::assertSame(80.0, $image->size()->height);
    }

    public function testEncodeOutputsSvgXml(): void
    {
        $image = $this->driver->create(new Size(50.0, 50.0), Color::rgb(0.0, 0.0, 1.0));
        $data = $this->driver->encode($image, ImageFormat::SVG);

        self::assertStringContainsString('<?xml', $data);
        self::assertStringContainsString('<svg', $data);
        self::assertStringContainsString('width="50"', $data);
        self::assertStringContainsString('height="50"', $data);
    }

    public function testEncodeIncludesBackground(): void
    {
        $image = $this->driver->create(new Size(20.0, 20.0), Color::rgb(1.0, 0.0, 0.0));
        $data = $this->driver->encode($image, ImageFormat::SVG);

        self::assertStringContainsString('<rect', $data);
        self::assertStringContainsString('rgb(255,0,0)', $data);
    }

    public function testLoadFromSvgData(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="150"></svg>';
        $loaded = $this->driver->load($svg);

        self::assertSame(200.0, $loaded->size()->width);
        self::assertSame(150.0, $loaded->size()->height);
    }

    public function testLoadFromViewBox(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 200"></svg>';
        $loaded = $this->driver->load($svg);

        self::assertSame(300.0, $loaded->size()->width);
        self::assertSame(200.0, $loaded->size()->height);
    }

    public function testLoadInvalidSvgThrows(): void
    {
        $this->expectException(DriverException::class);
        $this->driver->load('not svg data');
    }

    public function testLoadFileNotReadableThrows(): void
    {
        $this->expectException(DriverException::class);
        $this->driver->loadFile('/nonexistent/path/file.svg');
    }

    public function testSupportsOnlySvg(): void
    {
        self::assertTrue($this->driver->supports(ImageFormat::SVG));
        self::assertFalse($this->driver->supports(ImageFormat::PNG));
        self::assertFalse($this->driver->supports(ImageFormat::JPEG));
    }

    public function testUnsupportedFormatThrows(): void
    {
        $image = $this->driver->create(new Size(10.0, 10.0), Color::rgb(0.0, 0.0, 0.0));

        $this->expectException(FormatException::class);
        $this->driver->encode($image, ImageFormat::PNG);
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

    public function testFlip(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(1.0, 0.0, 0.0));

        $flipped = $image->flip(horizontal: true);

        self::assertSame(100.0, $flipped->size()->width);
        self::assertNotSame($image, $flipped);
    }

    public function testRotate90SwapsDimensions(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(1.0, 0.0, 0.0));

        $rotated = $image->rotate(90.0, Color::rgb(0.0, 0.0, 0.0));

        self::assertSame(50.0, $rotated->size()->width);
        self::assertSame(100.0, $rotated->size()->height);
    }

    public function testDrawingContext(): void
    {
        $image = $this->driver->create(new Size(100.0, 100.0), Color::rgb(1.0, 1.0, 1.0));

        $ctx = $image->drawingContext();

        self::assertInstanceOf(SvgDrawingContext::class, $ctx);
    }

    public function testDrawingProducesSvgElements(): void
    {
        $image = $this->driver->create(new Size(100.0, 100.0), Color::rgb(1.0, 1.0, 1.0));
        self::assertInstanceOf(SvgResource::class, $image);
        $ctx = $image->drawingContext();

        $ctx->setFillColor(Color::rgb(1.0, 0.0, 0.0));
        $ctx->rect(10.0, 10.0, 30.0, 30.0);
        $ctx->fill();

        $svg = $image->toSvg();
        self::assertStringContainsString('<path', $svg);
        self::assertStringContainsString('rgb(255,0,0)', $svg);
    }

    public function testImmutability(): void
    {
        $image = $this->driver->create(new Size(200.0, 100.0), Color::rgb(0.0, 0.0, 0.0));
        $resized = $image->resize(new Size(50.0, 25.0));

        self::assertSame(200.0, $image->size()->width);
        self::assertSame(50.0, $resized->size()->width);
    }

    public function testLoadFile(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="60"><rect width="80" height="60" fill="red"/></svg>';
        $tmp = tempnam(sys_get_temp_dir(), 'svg_test_');
        self::assertNotFalse($tmp);
        file_put_contents($tmp, $svg);

        try {
            $loaded = $this->driver->loadFile($tmp);
            self::assertSame(80.0, $loaded->size()->width);
            self::assertSame(60.0, $loaded->size()->height);
        } finally {
            @unlink($tmp);
        }
    }
}
