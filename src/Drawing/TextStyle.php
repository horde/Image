<?php

declare(strict_types=1);

namespace Horde\Image\Drawing;

final class TextStyle
{
    public readonly float $size;

    public function __construct(
        float|FontSize $size = 12.0,
        public readonly ?string $fontFamily = null,
        public readonly ?string $fontFile = null,
        public readonly float $angle = 0.0,
    ) {
        $this->size = $size instanceof FontSize ? $size->points() : $size;
    }
}
