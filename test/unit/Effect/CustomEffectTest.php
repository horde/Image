<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\Effect\Effect;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Imagick;
use RuntimeException;

#[CoversClass(ImagickResource::class)]
#[RequiresPhpExtension('imagick')]
final class CustomEffectTest extends TestCase
{
    private ImagickDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ImagickDriver();
    }

    public function testUserSuppliedEffectWorks(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(1.0, 1.0, 1.0));

        $doubleWidth = new class implements Effect {
            public function apply(ImageResource $image): ImageResource
            {
                if (!$image instanceof ImagickResource) {
                    throw new RuntimeException('Requires ImagickResource');
                }
                $imagick = clone $image->imagick();
                $width = $imagick->getImageWidth();
                $height = $imagick->getImageHeight();
                $imagick->resizeImage($width * 2, $height, Imagick::FILTER_LANCZOS, 1);
                return $image->withImagick($imagick);
            }
        };

        $result = $image->effect($doubleWidth);

        self::assertSame(200.0, $result->size()->width);
        self::assertSame(80.0, $result->size()->height);
    }

    public function testCustomEffectPreservesImmutability(): void
    {
        $image = $this->driver->create(new Size(100.0, 80.0), Color::rgb(0.0, 0.0, 0.0));

        $noop = new class implements Effect {
            public function apply(ImageResource $image): ImageResource
            {
                if (!$image instanceof ImagickResource) {
                    throw new RuntimeException('Requires ImagickResource');
                }
                $imagick = clone $image->imagick();
                return $image->withImagick($imagick);
            }
        };

        $result = $image->effect($noop);

        self::assertNotSame($image, $result);
        self::assertSame(100.0, $result->size()->width);
        self::assertSame(80.0, $result->size()->height);
    }

    public function testCustomEffectCanComposeWithShippedEffects(): void
    {
        $image = $this->driver->create(new Size(200.0, 150.0), Color::rgb(0.5, 0.3, 0.1));

        $halve = new class implements Effect {
            public function apply(ImageResource $image): ImageResource
            {
                if (!$image instanceof ImagickResource) {
                    throw new RuntimeException('Requires ImagickResource');
                }
                $imagick = clone $image->imagick();
                $w = (int) ($imagick->getImageWidth() / 2);
                $h = (int) ($imagick->getImageHeight() / 2);
                $imagick->resizeImage($w, $h, Imagick::FILTER_LANCZOS, 1);
                return $image->withImagick($imagick);
            }
        };

        $result = $image
            ->effect($halve)
            ->effect(new \Horde\Image\Effect\Border(width: 2));

        self::assertSame(104.0, $result->size()->width);
        self::assertSame(79.0, $result->size()->height);
    }
}
