<?php

declare(strict_types=1);

namespace Horde\Image\Geometry;

final readonly class Point
{
    public function __construct(
        public float $x,
        public float $y,
    ) {}

    public function translate(float $dx, float $dy): self
    {
        return new self($this->x + $dx, $this->y + $dy);
    }
}
