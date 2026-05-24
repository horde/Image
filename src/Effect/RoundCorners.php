<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Imagick;

final class RoundCorners implements Effect
{
    private readonly Color $resolvedBackground;
    private readonly Color $resolvedBorderColor;

    public function __construct(
        public readonly int $radius = 10,
        ?Color $background = null,
        public readonly int $border = 0,
        ?Color $borderColor = null,
    ) {
        $this->resolvedBackground = $background ?? Color::rgba(0.0, 0.0, 0.0, 0.0);
        $this->resolvedBorderColor = $borderColor ?? Color::rgba(0.0, 0.0, 0.0, 0.0);
    }

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('RoundCorners effect requires ImagickResource');
        }

        $imagick = clone $image->imagick();
        $imagick->roundCorners($this->radius, $this->radius);

        if ($this->border > 0 && $this->resolvedBorderColor->alpha() > 0.01) {
            $size = $imagick->getImageGeometry();
            $frame = new Imagick();
            $frame->newImage(
                $size['width'] + $this->border,
                $size['height'] + $this->border,
                ImagickDriver::colorToPixel($this->resolvedBorderColor),
            );
            $frame->setImageFormat($imagick->getImageFormat() ?: 'png');
            $frame->roundCorners($this->radius, $this->radius);
            $frame->compositeImage(
                $imagick,
                Imagick::COMPOSITE_OVER,
                (int) round($this->border / 2),
                (int) round($this->border / 2),
            );
            $imagick->clear();
            $imagick->addImage($frame);
            $frame->destroy();
        }

        if ($this->resolvedBackground->alpha() > 0.01) {
            $size = $imagick->getImageGeometry();
            $bg = new Imagick();
            $bg->newImage($size['width'], $size['height'], ImagickDriver::colorToPixel($this->resolvedBackground));
            $bg->setImageFormat($imagick->getImageFormat() ?: 'png');
            $bg->compositeImage($imagick, Imagick::COMPOSITE_OVER, 0, 0);
            $imagick->clear();
            $imagick->addImage($bg);
            $bg->destroy();
        }

        return $image->withImagick($imagick);
    }
}
