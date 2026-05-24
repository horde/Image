<?php

declare(strict_types=1);

namespace Horde\Image\Geometry;

final readonly class Size
{
    public function __construct(
        public float $width,
        public float $height,
    ) {}

    public function scale(float $factor): self
    {
        return new self($this->width * $factor, $this->height * $factor);
    }

    public function fitWithin(self $bounds): self
    {
        $ratio = min($bounds->width / $this->width, $bounds->height / $this->height);
        if ($ratio >= 1.0) {
            return $this;
        }
        return new self(
            round($this->width * $ratio),
            round($this->height * $ratio),
        );
    }

    public function cover(self $bounds): self
    {
        $ratio = max($bounds->width / $this->width, $bounds->height / $this->height);
        return new self(
            round($this->width * $ratio),
            round($this->height * $ratio),
        );
    }
}
