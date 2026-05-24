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

final class DropShadow implements Effect
{
    private readonly Color $resolvedBackground;

    public function __construct(
        public readonly int $distance = 5,
        public readonly float $sigma = 3.0,
        public readonly int $padding = 0,
        ?Color $background = null,
    ) {
        $this->resolvedBackground = $background ?? Color::rgba(0.0, 0.0, 0.0, 0.0);
    }

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('DropShadow effect requires ImagickResource');
        }

        $shadow = clone $image->imagick();
        $shadow->setImageBackgroundColor(new ImagickPixel('black'));
        $shadow->shadowImage(80, $this->sigma, $this->distance, $this->distance);

        $bgColor = ImagickDriver::colorToPixel($this->resolvedBackground);
        if ($this->resolvedBackground->alpha() > 0.01) {
            $size = $shadow->getImageGeometry();
            $bg = new Imagick();
            $bg->newImage($size['width'], $size['height'], $bgColor);
            $bg->setImageFormat($image->imagick()->getImageFormat() ?: 'png');
            $bg->compositeImage($shadow, Imagick::COMPOSITE_OVER, 0, 0);
            $shadow->clear();
            $shadow->addImage($bg);
            $bg->destroy();
        }

        $shadow->compositeImage($image->imagick(), Imagick::COMPOSITE_OVER, 0, 0);

        if ($this->padding > 0) {
            $shadow->borderImage($bgColor, $this->padding, $this->padding);
        }

        return $image->withImagick($shadow);
    }
}
