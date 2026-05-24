<?php

declare(strict_types=1);

/**
 * Sobel edge detection convolution filter.
 *
 * Applies a 3x3 Sobel kernel in the specified direction to detect
 * horizontal or vertical edges. Commonly used as a first step in
 * barcode region detection (horizontal gradient highlights vertical bars).
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Image\Filter;

final readonly class Sobel implements Filter
{
    public function __construct(
        public SobelDirection $direction = SobelDirection::Horizontal,
    ) {}
}
