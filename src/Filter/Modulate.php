<?php

declare(strict_types=1);

namespace Horde\Image\Filter;

final class Modulate implements Filter
{
    /**
     * @param float $brightness Brightness percentage (100.0 = no change)
     * @param float $saturation Saturation percentage (100.0 = no change)
     * @param float $hue Hue rotation percentage (100.0 = no change)
     */
    public function __construct(
        public readonly float $brightness = 100.0,
        public readonly float $saturation = 100.0,
        public readonly float $hue = 100.0,
    ) {}
}
