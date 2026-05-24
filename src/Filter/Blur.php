<?php

declare(strict_types=1);

namespace Horde\Image\Filter;

final class Blur implements Filter
{
    public function __construct(
        public readonly float $radius = 0.0,
        public readonly float $sigma = 1.0,
    ) {}
}
