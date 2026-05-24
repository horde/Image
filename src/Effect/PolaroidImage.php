<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Imagick;
use ImagickDraw;
use ImagickPixel;

final class PolaroidImage implements Effect
{
    private readonly Color $resolvedBackground;
    private readonly Color $resolvedShadowColor;

    public function __construct(
        public readonly float $angle = 0.0,
        ?Color $background = null,
        ?Color $shadowColor = null,
    ) {
        $this->resolvedBackground = $background ?? Color::rgba(0.0, 0.0, 0.0, 0.0);
        $this->resolvedShadowColor = $shadowColor ?? Color::rgb(0.0, 0.0, 0.0);
    }

    public function background(): Color
    {
        return $this->resolvedBackground;
    }

    public function shadowColor(): Color
    {
        return $this->resolvedShadowColor;
    }

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('PolaroidImage effect requires ImagickResource');
        }

        $imagick = clone $image->imagick();

        $imagick->setImageBackgroundColor($this->colorToPixel($this->resolvedShadowColor));
        $imagick->polaroidImage(new ImagickDraw(), $this->angle);

        $geo = $imagick->getImageGeometry();
        $canvas = new Imagick();
        $canvas->newImage($geo['width'], $geo['height'], $this->colorToPixel($this->resolvedBackground));
        $canvas->setImageFormat('png');
        $canvas->compositeImage($imagick, Imagick::COMPOSITE_OVER, 0, 0);

        $imagick->destroy();

        return $image->withImagick($canvas);
    }

    private function colorToPixel(Color $color): ImagickPixel
    {
        $r = (int) round($color->red() * 255);
        $g = (int) round($color->green() * 255);
        $b = (int) round($color->blue() * 255);
        $a = $color->alpha();

        return new ImagickPixel(sprintf('rgba(%d,%d,%d,%s)', $r, $g, $b, $a));
    }
}
