<?php

declare(strict_types=1);

namespace Horde\Image\Filter;

final class Gamma implements Filter
{
    /**
     * @param float $gamma Gamma correction value (> 0.0, 1.0 = no change)
     */
    public function __construct(
        public readonly float $gamma = 1.0,
    ) {}
}
