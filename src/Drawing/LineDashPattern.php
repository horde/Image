<?php

declare(strict_types=1);

namespace Horde\Image\Drawing;

final class LineDashPattern
{
    /**
     * @param array<int, float> $dashArray
     */
    public function __construct(
        public readonly array $dashArray,
        public readonly float $dashPhase = 0.0,
    ) {}

    public static function solid(): self
    {
        return new self([], 0.0);
    }
}
