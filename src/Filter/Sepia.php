<?php

declare(strict_types=1);

namespace Horde\Image\Filter;

final class Sepia implements Filter
{
    public function __construct(
        public readonly float $threshold = 80.0,
    ) {}
}
