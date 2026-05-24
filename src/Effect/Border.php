<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Imagick;
use ImagickPixel;

final class Border implements Effect
{
    private readonly Color $resolvedColor;

    public function __construct(
        ?Color $color = null,
        public readonly int $width = 1,
        public readonly bool $preserveTransparency = true,
    ) {
        $this->resolvedColor = $color ?? Color::rgb(0.0, 0.0, 0.0);
    }

    public function color(): Color
    {
        return $this->resolvedColor;
    }

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('Border effect requires ImagickResource');
        }

        $imagick = clone $image->imagick();
        $pixel = ImagickDriver::colorToPixel($this->resolvedColor);

        if ($this->preserveTransparency) {
            self::frameImage($imagick, $pixel, $this->width, $this->width);
        } else {
            $imagick->borderImage($pixel, $this->width, $this->width);
        }

        return $image->withImagick($imagick);
    }

    private static function frameImage(Imagick $imagick, ImagickPixel $color, int $width, int $height): void
    {
        $geo = $imagick->getImageGeometry();
        $newWidth = $geo['width'] + (2 * $width);
        $newHeight = $geo['height'] + (2 * $height);

        $frame = new Imagick();
        $frame->newImage($newWidth, $newHeight, $color);
        $frame->setImageFormat($imagick->getImageFormat() ?: 'png');
        $frame->compositeImage($imagick, Imagick::COMPOSITE_OVER, $width, $height);

        $imagick->clear();
        $imagick->addImage($frame);
        $frame->destroy();
    }
}
