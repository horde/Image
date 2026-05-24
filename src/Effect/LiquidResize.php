<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;

final class LiquidResize implements Effect
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly float $deltaX = 0.0,
        public readonly float $rigidity = 0.0,
    ) {}

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('LiquidResize effect requires ImagickResource');
        }

        $imagick = clone $image->imagick();
        $imagick->liquidRescaleImage($this->width, $this->height, $this->deltaX, $this->rigidity);

        return $image->withImagick($imagick);
    }
}
