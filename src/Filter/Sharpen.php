<?php

declare(strict_types=1);

namespace Horde\Image\Filter;

final class Sharpen implements Filter
{
    public function __construct(
        public readonly float $radius = 0.0,
        public readonly float $sigma = 1.0,
        public readonly float $amount = 1.0,
        public readonly float $threshold = 0.05,
    ) {}
}
