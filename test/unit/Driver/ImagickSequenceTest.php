<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Size;
use Horde\Image\Sequence\AnimationOptions;
use Horde\Image\Sequence\DisposalMethod;
use Horde\Image\Sequence\Frame;
use Horde\Image\Sequence\ImageSequence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImagickDriver::class)]
#[CoversClass(ImageSequence::class)]
#[CoversClass(Frame::class)]
#[RequiresPhpExtension('imagick')]
final class ImagickSequenceTest extends TestCase
{
    private ImagickDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ImagickDriver();
    }

    private function tiffFixture(): string
    {
        return __DIR__ . '/../../two_page.tif.tiff';
    }

    private function createFrame(Color $color, float $w = 50.0, float $h = 50.0): ImagickResource
    {
        return $this->driver->create(new Size($w, $h), $color);
    }

    public function testLoadFileSequenceTiff(): void
    {
        $seq = $this->driver->loadFileSequence($this->tiffFixture());

        self::assertCount(2, $seq);
        self::assertInstanceOf(ImagickResource::class, $seq->frameAt(0)->image);
        self::assertInstanceOf(ImagickResource::class, $seq->frameAt(1)->image);
        self::assertGreaterThan(0.0, $seq->canvasSize()->width);
        self::assertGreaterThan(0.0, $seq->canvasSize()->height);
    }

    public function testLoadSequenceFromData(): void
    {
        $data = file_get_contents($this->tiffFixture());
        $seq = $this->driver->loadSequence($data);

        self::assertCount(2, $seq);
    }

    public function testLoadFileSequenceSingleImage(): void
    {
        $fixturePath = __DIR__ . '/../../Horde/Image/Fixtures/img_exif.jpg';
        $seq = $this->driver->loadFileSequence($fixturePath);

        self::assertCount(1, $seq);
    }

    public function testEncodeSequenceAnimatedGif(): void
    {
        $red = $this->createFrame(Color::rgb(1.0, 0.0, 0.0));
        $green = $this->createFrame(Color::rgb(0.0, 1.0, 0.0));
        $blue = $this->createFrame(Color::rgb(0.0, 0.0, 1.0));

        $seq = ImageSequence::fromFrames(
            [
                new Frame($red, delay: 100),
                new Frame($green, delay: 200),
                new Frame($blue, delay: 150),
            ],
            new Size(50.0, 50.0),
        );

        $gifData = $this->driver->encodeSequence(
            $seq,
            ImageFormat::GIF,
            animation: new AnimationOptions(loopCount: 0, optimize: false),
        );

        self::assertStringStartsWith('GIF', $gifData);
    }

    public function testRoundTripAnimatedGif(): void
    {
        $frame1 = $this->createFrame(Color::rgb(1.0, 0.0, 0.0));
        $frame2 = $this->createFrame(Color::rgb(0.0, 1.0, 0.0));

        $seq = ImageSequence::fromFrames(
            [
                new Frame($frame1, delay: 100),
                new Frame($frame2, delay: 200),
            ],
            new Size(50.0, 50.0),
        );

        $gifData = $this->driver->encodeSequence(
            $seq,
            ImageFormat::GIF,
            animation: new AnimationOptions(loopCount: 0, optimize: false),
        );

        $decoded = $this->driver->loadSequence($gifData);

        self::assertCount(2, $decoded);
        self::assertEqualsWithDelta(100, $decoded->frameAt(0)->delay, 10);
        self::assertEqualsWithDelta(200, $decoded->frameAt(1)->delay, 10);
    }

    public function testEncodeSequenceWithOptimize(): void
    {
        $frame1 = $this->createFrame(Color::rgb(1.0, 0.0, 0.0));
        $frame2 = $this->createFrame(Color::rgb(0.0, 1.0, 0.0));
        $frame3 = $this->createFrame(Color::rgb(0.0, 0.0, 1.0));

        $seq = ImageSequence::fromFrames(
            [new Frame($frame1, delay: 100), new Frame($frame2, delay: 100), new Frame($frame3, delay: 100)],
            new Size(50.0, 50.0),
        );

        $optimized = $this->driver->encodeSequence(
            $seq,
            ImageFormat::GIF,
            animation: new AnimationOptions(optimize: true),
        );

        self::assertStringStartsWith('GIF', $optimized);
        $decoded = $this->driver->loadSequence($optimized);
        self::assertCount(3, $decoded);
    }

    public function testCoalesce(): void
    {
        $frame1 = $this->createFrame(Color::rgb(1.0, 0.0, 0.0));
        $frame2 = $this->createFrame(Color::rgb(0.0, 1.0, 0.0));

        $seq = ImageSequence::fromFrames(
            [new Frame($frame1, delay: 100), new Frame($frame2, delay: 200)],
            new Size(50.0, 50.0),
        );

        $coalesced = $this->driver->coalesce($seq);

        self::assertCount(2, $coalesced);
        self::assertSame(50.0, $coalesced->frameAt(0)->image->size()->width);
        self::assertSame(50.0, $coalesced->frameAt(1)->image->size()->width);
    }

    public function testOptimize(): void
    {
        $frame1 = $this->createFrame(Color::rgb(1.0, 0.0, 0.0));
        $frame2 = $this->createFrame(Color::rgb(0.0, 1.0, 0.0));

        $seq = ImageSequence::fromFrames(
            [new Frame($frame1, delay: 100), new Frame($frame2, delay: 200)],
            new Size(50.0, 50.0),
        );

        $optimized = $this->driver->optimize($seq);

        self::assertCount(2, $optimized);
    }

    public function testFrameDelayDefaultApplied(): void
    {
        $frame1 = $this->createFrame(Color::rgb(1.0, 0.0, 0.0));
        $frame2 = $this->createFrame(Color::rgb(0.0, 1.0, 0.0));

        $seq = ImageSequence::fromFrames(
            [new Frame($frame1, delay: 0), new Frame($frame2, delay: 0)],
            new Size(50.0, 50.0),
        );

        $gifData = $this->driver->encodeSequence(
            $seq,
            ImageFormat::GIF,
            animation: new AnimationOptions(defaultDelay: 500, optimize: false),
        );

        $decoded = $this->driver->loadSequence($gifData);
        self::assertEqualsWithDelta(500, $decoded->frameAt(0)->delay, 10);
        self::assertEqualsWithDelta(500, $decoded->frameAt(1)->delay, 10);
    }

    public function testSequencePreservesFrameSize(): void
    {
        $seq = $this->driver->loadFileSequence($this->tiffFixture());

        $firstSize = $seq->frameAt(0)->image->size();
        $secondSize = $seq->frameAt(1)->image->size();

        self::assertGreaterThan(0.0, $firstSize->width);
        self::assertGreaterThan(0.0, $firstSize->height);
        self::assertGreaterThan(0.0, $secondSize->width);
        self::assertGreaterThan(0.0, $secondSize->height);
    }
}
