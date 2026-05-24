<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;

final class CenterCrop implements Effect
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {}

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('CenterCrop effect requires ImagickResource');
        }

        $imagick = clone $image->imagick();
        $imagick->cropThumbnailImage($this->width, $this->height);
        $imagick->setImagePage(0, 0, 0, 0);

        return $image->withImagick($imagick);
    }
}
