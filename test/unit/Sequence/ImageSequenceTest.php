<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Sequence;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\Geometry\Size;
use Horde\Image\ImageException;
use Horde\Image\Sequence\Frame;
use Horde\Image\Sequence\ImageSequence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageSequence::class)]
#[RequiresPhpExtension('imagick')]
final class ImageSequenceTest extends TestCase
{
    private ImagickDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ImagickDriver();
    }

    private function createImage(float $w = 100.0, float $h = 80.0): ImagickResource
    {
        return $this->driver->create(new Size($w, $h), Color::rgb(0.5, 0.5, 0.5));
    }

    public function testSingleFactory(): void
    {
        $image = $this->createImage();
        $seq = ImageSequence::single($image);

        self::assertCount(1, $seq);
        self::assertSame($image, $seq->first()->image);
        self::assertSame(100.0, $seq->canvasSize()->width);
        self::assertSame(80.0, $seq->canvasSize()->height);
    }

    public function testFromFrames(): void
    {
        $img1 = $this->createImage(50.0, 50.0);
        $img2 = $this->createImage(50.0, 50.0);
        $frames = [
            new Frame($img1, delay: 100),
            new Frame($img2, delay: 200),
        ];

        $seq = ImageSequence::fromFrames($frames, new Size(50.0, 50.0));

        self::assertCount(2, $seq);
        self::assertSame(100, $seq->frameAt(0)->delay);
        self::assertSame(200, $seq->frameAt(1)->delay);
    }

    public function testEmptyFramesThrows(): void
    {
        $this->expectException(ImageException::class);
        ImageSequence::fromFrames([], new Size(100.0, 100.0));
    }

    public function testFrameAtOutOfBounds(): void
    {
        $seq = ImageSequence::single($this->createImage());

        $this->expectException(ImageException::class);
        $seq->frameAt(5);
    }

    public function testFrameAtNegativeIndex(): void
    {
        $seq = ImageSequence::single($this->createImage());

        $this->expectException(ImageException::class);
        $seq->frameAt(-1);
    }

    public function testAppend(): void
    {
        $img1 = $this->createImage();
        $img2 = $this->createImage();
        $seq = ImageSequence::single($img1);
        $appended = $seq->append(new Frame($img2, delay: 300));

        self::assertCount(1, $seq);
        self::assertCount(2, $appended);
        self::assertSame(300, $appended->frameAt(1)->delay);
    }

    public function testWithFrameAt(): void
    {
        $img1 = $this->createImage();
        $img2 = $this->createImage();
        $seq = ImageSequence::fromFrames(
            [new Frame($img1, delay: 100), new Frame($img1, delay: 200)],
            new Size(100.0, 80.0),
        );

        $modified = $seq->withFrameAt(1, new Frame($img2, delay: 999));

        self::assertSame(200, $seq->frameAt(1)->delay);
        self::assertSame(999, $modified->frameAt(1)->delay);
    }

    public function testWithFrameAtOutOfBounds(): void
    {
        $seq = ImageSequence::single($this->createImage());

        $this->expectException(ImageException::class);
        $seq->withFrameAt(5, new Frame($this->createImage()));
    }

    public function testMap(): void
    {
        $img = $this->createImage();
        $seq = ImageSequence::fromFrames(
            [new Frame($img, delay: 100), new Frame($img, delay: 200), new Frame($img, delay: 300)],
            new Size(100.0, 80.0),
        );

        $doubled = $seq->map(fn(Frame $f, int $i) => $f->withDelay($f->delay * 2));

        self::assertSame(200, $doubled->frameAt(0)->delay);
        self::assertSame(400, $doubled->frameAt(1)->delay);
        self::assertSame(600, $doubled->frameAt(2)->delay);
        // Original unchanged
        self::assertSame(100, $seq->frameAt(0)->delay);
    }

    public function testSlice(): void
    {
        $img = $this->createImage();
        $seq = ImageSequence::fromFrames(
            [new Frame($img, delay: 10), new Frame($img, delay: 20), new Frame($img, delay: 30)],
            new Size(100.0, 80.0),
        );

        $sliced = $seq->slice(1, 2);

        self::assertCount(2, $sliced);
        self::assertSame(20, $sliced->frameAt(0)->delay);
        self::assertSame(30, $sliced->frameAt(1)->delay);
    }

    public function testSliceEmptyThrows(): void
    {
        $img = $this->createImage();
        $seq = ImageSequence::single($img);

        $this->expectException(ImageException::class);
        $seq->slice(5);
    }

    public function testWithCanvasSize(): void
    {
        $seq = ImageSequence::single($this->createImage(100.0, 80.0));
        $resized = $seq->withCanvasSize(new Size(200.0, 150.0));

        self::assertSame(100.0, $seq->canvasSize()->width);
        self::assertSame(200.0, $resized->canvasSize()->width);
        self::assertSame(150.0, $resized->canvasSize()->height);
    }

    public function testIterable(): void
    {
        $img = $this->createImage();
        $seq = ImageSequence::fromFrames(
            [new Frame($img, delay: 1), new Frame($img, delay: 2), new Frame($img, delay: 3)],
            new Size(100.0, 80.0),
        );

        $delays = [];
        foreach ($seq as $frame) {
            $delays[] = $frame->delay;
        }

        self::assertSame([1, 2, 3], $delays);
    }

    public function testFramesReturnsArray(): void
    {
        $img = $this->createImage();
        $seq = ImageSequence::fromFrames(
            [new Frame($img), new Frame($img)],
            new Size(100.0, 80.0),
        );

        $frames = $seq->frames();
        self::assertCount(2, $frames);
        self::assertContainsOnlyInstancesOf(Frame::class, $frames);
    }
}
