<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\DriverException;
use Horde\Image\Effect\Effect;
use Horde\Image\Filter\Filter;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;

final class NullResource implements ImageResource
{
    public function __construct(
        private readonly Size $size,
    ) {}

    public function size(): Size
    {
        return $this->size;
    }

    public function resize(Size $size): static
    {
        return new static($size);
    }

    public function crop(Rectangle $region): static
    {
        return new static($region->size);
    }

    public function rotate(float $angleDeg, Color $background): static
    {
        $normalized = ((int) $angleDeg) % 360;
        if ($normalized < 0) {
            $normalized += 360;
        }

        if ($normalized === 90 || $normalized === 270) {
            return new static(new Size($this->size->height, $this->size->width));
        }

        return new static($this->size);
    }

    public function flip(bool $horizontal = false, bool $vertical = false): static
    {
        return new static($this->size);
    }

    public function apply(Filter $filter): static
    {
        return new static($this->size);
    }

    public function effect(Effect $effect): static
    {
        return new static($this->size);
    }

    public function drawingContext(): DrawingContext
    {
        throw new DriverException('NullDriver does not support drawing operations');
    }
}
