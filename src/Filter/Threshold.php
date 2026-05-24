<?php

declare(strict_types=1);

/**
 * Binarize an image by thresholding pixel luminance.
 *
 * Pixels with luminance below the threshold become black (0,0,0),
 * pixels at or above become white (255,255,255).
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Image\Filter;

final readonly class Threshold implements Filter
{
    /**
     * @param int $level 0-255; pixels below become black, at or above become white
     */
    public function __construct(
        public int $level = 128,
    ) {}
}
