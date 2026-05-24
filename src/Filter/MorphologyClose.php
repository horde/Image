<?php

declare(strict_types=1);

/**
 * Morphological closing filter.
 *
 * Applies a dilation followed by an erosion using a rectangular
 * structuring element. Used to bridge small gaps between bars in
 * barcode detection pipelines.
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Image\Filter;

final readonly class MorphologyClose implements Filter
{
    public function __construct(
        public int $width = 21,
        public int $height = 7,
    ) {}
}
