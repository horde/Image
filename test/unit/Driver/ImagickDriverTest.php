<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickDrawingContext;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Horde\Image\Filter\Blur;
use Horde\Image\Filter\Brightness;
use Horde\Image\Filter\Colorize;
use Horde\Image\Filter\Contrast;
use Horde\Image\Filter\Gamma;
use Horde\Image\Filter\Grayscale;
use Horde\Image\Filter\Modulate;
use Horde\Image\Filter\Negate;
use Horde\Image\Filter\Pixelate;
use Horde\Image\Filter\Sepia;
use Horde\Image\Filter\Sharpen;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\FormatException;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImagickDriver::class)]
#[CoversClass(ImagickResource::class)]
#[RequiresPhpExtension('imagick')]
final class ImagickDriverTest extends TestCase
{
    private ImagickDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ImagickDriver();
    }

    private function fixturePath(): string
    {
        return __DIR__ . '/../../Horde/Image/Fixtures/img_exif.jpg';
    }

    private function loadFixture(): ImagickResource
    {
        return $this->driver->loadFile($this->fixturePath());
    }

    // --- ImagickDriver tests ---

    public function testLoadFile(): void
    {
        $image = $this->loadFixture();

        self::assertInstanceOf(ImagickResource::class, $image);
        self::assertGreaterThan(0.0, $image->size()->width);
        self::assertGreaterThan(0.0, $image->size()->height);
    }

    public function testLoadFileNotReadable(): void
    {
        $this->expectException(DriverException::class);
        $this->driver->loadFile('/nonexistent/path.jpg');
    }

    public function testLoad(): void
    {
        $data = file_get_contents($this->fixturePath());
        $image = $this->driver->load($data);

        self::assertGreaterThan(0.0, $image->size()->width);
    }

    public function testLoadInvalidData(): void
    {
        $this->expectException(DriverException::class);
        $this->driver->load('not-an-image');
    }

    public function testCreate(): void
    {
        $image = $this->driver->create(new Size(200.0, 150.0), Color::rgb(1.0, 0.0, 0.0));

        self::assertSame(200.0, $image->size()->width);
        self::assertSame(150.0, $image->size()->height);
    }

    public function testSupportsPng(): void
    {
        self::assertTrue($this->driver->supports(ImageFormat::PNG));
    }

    public function testSupportsJpeg(): void
    {
        self::assertTrue($this->driver->supports(ImageFormat::JPEG));
    }

    public function testEncodePng(): void
    {
        $image = $this->driver->create(new Size(10.0, 10.0), Color::rgb(0.0, 0.0, 0.0));
        $data = $this->driver->encode($image, ImageFormat::PNG);

        self::assertStringStartsWith("\x89PNG", $data);
    }

    public function testEncodeJpeg(): void
    {
        $image = $this->driver->create(new Size(10.0, 10.0), Color::rgb(0.0, 0.0, 0.0));
        $data = $this->driver->encode($image, ImageFormat::JPEG);

        self::assertStringStartsWith("\xff\xd8\xff", $data);
    }

    public function testEncodeWithQuality(): void
    {
        $image = $this->loadFixture();
        $highQ = $this->driver->encode($image, ImageFormat::JPEG, new EncodeOptions(quality: 95));
        $lowQ = $this->driver->encode($image, ImageFormat::JPEG, new EncodeOptions(quality: 10));

        self::assertGreaterThan(strlen($lowQ), strlen($highQ));
    }

    public function testEncodeStripMetadata(): void
    {
        $image = $this->loadFixture();
        $withMeta = $this->driver->encode($image, ImageFormat::JPEG);
        $stripped = $this->driver->encode($image, ImageFormat::JPEG, new EncodeOptions(stripMetadata: true));

        self::assertLessThan(strlen($withMeta), strlen($stripped));
    }

    // --- ImagickResource tests ---

    public function testResize(): void
    {
        $image = $this->loadFixture();
        $resized = $image->resize(new Size(100.0, 80.0));

        self::assertSame(100.0, $resized->size()->width);
        self::assertSame(80.0, $resized->size()->height);
        // Original unchanged
        self::assertNotSame(100.0, $image->size()->width);
    }

    public function testCrop(): void
    {
        $image = $this->loadFixture();
        $cropped = $image->crop(Rectangle::fromCoordinates(0.0, 0.0, 50.0, 30.0));

        self::assertSame(50.0, $cropped->size()->width);
        self::assertSame(30.0, $cropped->size()->height);
    }

    public function testRotate(): void
    {
        $image = $this->driver->create(new Size(100.0, 50.0), Color::rgb(1.0, 1.0, 1.0));
        $rotated = $image->rotate(90.0, Color::rgb(0.0, 0.0, 0.0));

        self::assertSame(50.0, $rotated->size()->width);
        self::assertSame(100.0, $rotated->size()->height);
    }

    public function testFlipVertical(): void
    {
        $image = $this->loadFixture();
        $flipped = $image->flip(vertical: true);

        self::assertSame($image->size()->width, $flipped->size()->width);
        self::assertSame($image->size()->height, $flipped->size()->height);
    }

    public function testFlipHorizontal(): void
    {
        $image = $this->loadFixture();
        $flipped = $image->flip(horizontal: true);

        self::assertSame($image->size()->width, $flipped->size()->width);
    }

    public function testDrawingContext(): void
    {
        $image = $this->driver->create(new Size(100.0, 100.0), Color::rgb(1.0, 1.0, 1.0));
        $ctx = $image->drawingContext();

        self::assertInstanceOf(ImagickDrawingContext::class, $ctx);
    }

    // --- Filter tests ---

    public function testApplyGrayscale(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Grayscale());

        self::assertSame($image->size()->width, $result->size()->width);
        self::assertSame($image->size()->height, $result->size()->height);
    }

    public function testApplySepia(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Sepia(threshold: 90.0));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplyBlur(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Blur(sigma: 2.0));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplySharpen(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Sharpen(amount: 1.5));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplyNegate(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Negate());

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplyPixelate(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Pixelate(size: 8));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplyBrightness(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Brightness(level: 20.0));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplyContrast(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Contrast(level: -15.0));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplyGamma(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Gamma(gamma: 1.5));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplyColorize(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Colorize(color: Color::rgb(1.0, 0.0, 0.0), opacity: 0.3));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testApplyModulate(): void
    {
        $image = $this->loadFixture();
        $result = $image->apply(new Modulate(brightness: 110.0, saturation: 80.0));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testFilterChaining(): void
    {
        $image = $this->loadFixture();
        $result = $image
            ->apply(new Grayscale())
            ->apply(new Blur(sigma: 1.0))
            ->apply(new Sharpen(amount: 2.0));

        self::assertSame($image->size()->width, $result->size()->width);
    }

    public function testFilterImmutability(): void
    {
        $image = $this->loadFixture();
        $original = $this->driver->encode($image, ImageFormat::PNG);

        $image->apply(new Negate());

        $afterCall = $this->driver->encode($image, ImageFormat::PNG);
        self::assertSame($original, $afterCall);
    }
}
