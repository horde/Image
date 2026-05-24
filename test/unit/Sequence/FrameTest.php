<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Sequence;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\Geometry\Size;
use Horde\Image\Sequence\DisposalMethod;
use Horde\Image\Sequence\Frame;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(Frame::class)]
#[RequiresPhpExtension('imagick')]
final class FrameTest extends TestCase
{
    private function createImage(): ImagickResource
    {
        $driver = new ImagickDriver();
        return $driver->create(new Size(50.0, 40.0), Color::rgb(1.0, 0.0, 0.0));
    }

    public function testConstructorDefaults(): void
    {
        $image = $this->createImage();
        $frame = new Frame($image);

        self::assertSame($image, $frame->image);
        self::assertSame(0, $frame->delay);
        self::assertSame(DisposalMethod::None, $frame->disposal);
        self::assertSame(0, $frame->x);
        self::assertSame(0, $frame->y);
    }

    public function testConstructorCustom(): void
    {
        $image = $this->createImage();
        $frame = new Frame(
            image: $image,
            delay: 200,
            disposal: DisposalMethod::Background,
            x: 10,
            y: 20,
        );

        self::assertSame(200, $frame->delay);
        self::assertSame(DisposalMethod::Background, $frame->disposal);
        self::assertSame(10, $frame->x);
        self::assertSame(20, $frame->y);
    }

    public function testWithDelay(): void
    {
        $image = $this->createImage();
        $frame = new Frame($image, delay: 100);
        $modified = $frame->withDelay(500);

        self::assertSame(100, $frame->delay);
        self::assertSame(500, $modified->delay);
        self::assertSame($frame->image, $modified->image);
    }

    public function testWithImage(): void
    {
        $image1 = $this->createImage();
        $image2 = $this->createImage();
        $frame = new Frame($image1, delay: 150);
        $modified = $frame->withImage($image2);

        self::assertSame($image1, $frame->image);
        self::assertSame($image2, $modified->image);
        self::assertSame(150, $modified->delay);
    }

    public function testWithDisposal(): void
    {
        $image = $this->createImage();
        $frame = new Frame($image);
        $modified = $frame->withDisposal(DisposalMethod::Previous);

        self::assertSame(DisposalMethod::None, $frame->disposal);
        self::assertSame(DisposalMethod::Previous, $modified->disposal);
    }

    public function testWithOffset(): void
    {
        $image = $this->createImage();
        $frame = new Frame($image, x: 5, y: 10);
        $modified = $frame->withOffset(30, 40);

        self::assertSame(5, $frame->x);
        self::assertSame(10, $frame->y);
        self::assertSame(30, $modified->x);
        self::assertSame(40, $modified->y);
    }
}
