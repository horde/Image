<?php

declare(strict_types=1);

namespace Horde\Image\Filter;

final class Contrast implements Filter
{
    /**
     * @param float $level Contrast adjustment (-100.0 to +100.0)
     */
    public function __construct(
        public readonly float $level = 0.0,
    ) {}
}
