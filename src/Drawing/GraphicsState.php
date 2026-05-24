<?php

declare(strict_types=1);

namespace Horde\Image\Drawing;

final class GraphicsState
{
    public function __construct(
        public readonly ?float $fillAlpha = null,
        public readonly ?float $strokeAlpha = null,
        public readonly ?BlendMode $blendMode = null,
    ) {}

    public static function alpha(float $fill, ?float $stroke = null): self
    {
        return new self(fillAlpha: $fill, strokeAlpha: $stroke ?? $fill);
    }

    public static function blendMode(BlendMode $mode): self
    {
        return new self(blendMode: $mode);
    }
}
