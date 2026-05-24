<?php

declare(strict_types=1);

/**
 * Contract for per-pixel read access to image data.
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Image\Driver;

interface PixelReader
{
    /**
     * @return array{int, int, int} RGB values 0-255
     */
    public function getPixelRgb(int $x, int $y): array;

    /**
     * BT.601 luminance: 0.299*R + 0.587*G + 0.114*B
     *
     * @return int Luminance 0-255
     */
    public function getLuminance(int $x, int $y): int;
}
