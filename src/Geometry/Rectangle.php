<?php

declare(strict_types=1);

namespace Horde\Image\Geometry;

final readonly class Rectangle
{
    public function __construct(
        public Point $origin,
        public Size $size,
    ) {}

    public static function fromCoordinates(float $x1, float $y1, float $x2, float $y2): self
    {
        return new self(
            new Point(min($x1, $x2), min($y1, $y2)),
            new Size(abs($x2 - $x1), abs($y2 - $y1)),
        );
    }

    public function right(): float
    {
        return $this->origin->x + $this->size->width;
    }

    public function bottom(): float
    {
        return $this->origin->y + $this->size->height;
    }
}
