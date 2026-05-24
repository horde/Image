<?php

declare(strict_types=1);

/**
 * Barcode region detection effect.
 *
 * Multi-step pipeline: grayscale, Sobel horizontal gradient, threshold,
 * morphological close. The result is a binary image where connected
 * white regions indicate likely barcode locations.
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Image\Effect;

use Horde\Image\Driver\ImageResource;
use Horde\Image\Filter\Grayscale;
use Horde\Image\Filter\MorphologyClose;
use Horde\Image\Filter\Sobel;
use Horde\Image\Filter\SobelDirection;
use Horde\Image\Filter\Threshold;

final class BarcodeRegionDetect implements Effect
{
    public function __construct(
        private readonly int $closeWidth = 21,
        private readonly int $closeHeight = 7,
        private readonly float $threshold = 0.3,
    ) {}

    public function apply(ImageResource $image): ImageResource
    {
        $result = $image->apply(new Grayscale());
        $result = $result->apply(new Sobel(SobelDirection::Horizontal));
        $result = $result->apply(new Threshold($this->threshold));
        $result = $result->apply(new MorphologyClose($this->closeWidth, $this->closeHeight));

        return $result;
    }
}
