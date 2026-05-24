<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Imagick;

final class Composite implements Effect
{
    public function __construct(
        public readonly ImageResource $overlay,
        public readonly ?int $x = null,
        public readonly ?int $y = null,
        public readonly int $compositeOp = Imagick::COMPOSITE_OVER,
    ) {}

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('Composite effect requires ImagickResource');
        }
        if (!$this->overlay instanceof ImagickResource) {
            throw new DriverException('Composite overlay must be an ImagickResource');
        }

        $imagick = clone $image->imagick();
        $overlayIm = $this->overlay->imagick();

        $x = $this->x;
        $y = $this->y;

        if ($x === null || $y === null) {
            $baseGeo = $imagick->getImageGeometry();
            $overlayGeo = $overlayIm->getImageGeometry();
            $x ??= (int) round(($baseGeo['width'] - $overlayGeo['width']) / 2);
            $y ??= (int) round(($baseGeo['height'] - $overlayGeo['height']) / 2);
        }

        $imagick->compositeImage($overlayIm, $this->compositeOp, $x, $y); // @phpstan-ignore argument.type

        return $image->withImagick($imagick);
    }
}
