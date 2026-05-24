<?php

declare(strict_types=1);

namespace Horde\Image\Filter;

final class Pixelate implements Filter
{
    public function __construct(
        public readonly int $size = 10,
    ) {}
}
