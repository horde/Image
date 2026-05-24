<?php

declare(strict_types=1);

namespace Horde\Image\Format;

final class DecodeOptions
{
    public function __construct(
        public readonly bool $autoOrient = true,
        public readonly ?int $maxWidth = null,
        public readonly ?int $maxHeight = null,
    ) {}
}
