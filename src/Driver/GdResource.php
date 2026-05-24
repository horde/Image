<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\DriverException;
use Horde\Image\Effect\Effect;
use Horde\Image\Filter\Brightness;
use Horde\Image\Filter\Contrast;
use Horde\Image\Filter\Filter;
use Horde\Image\Filter\Gamma;
use Horde\Image\Filter\Grayscale;
use Horde\Image\Filter\Negate;
use Horde\Image\Filter\Sepia;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use GdImage;

final class GdResource implements ImageResource
{
    public function __construct(
        private GdImage $gd,
        private readonly GdDriver $driver, // @phpstan-ignore property.onlyWritten
    ) {}

    public function __clone()
    {
        $w = imagesx($this->gd);
        $h = imagesy($this->gd);
        $copy = imagecreatetruecolor($w, $h);
        if ($copy === false) {
            throw new DriverException('Failed to clone GD image');
        }
        imagesavealpha($copy, true);
        imagealphablending($copy, false);
        imagecopy($copy, $this->gd, 0, 0, 0, 0, $w, $h);
        imagealphablending($copy, true);
        $this->gd = $copy;
    }

    public function gd(): GdImage
    {
        return $this->gd;
    }

    public function size(): Size
    {
        return new Size((float) imagesx($this->gd), (float) imagesy($this->gd));
    }

    public function resize(Size $size): static
    {
        $clone = clone $this;
        $newW = max(1, (int) $size->width);
        $newH = max(1, (int) $size->height);

        $resized = imagecreatetruecolor($newW, $newH);
        if ($resized === false) {
            throw new DriverException('Failed to create resized image');
        }

        imagesavealpha($resized, true);
        imagealphablending($resized, false);
        imagecopyresampled($resized, $clone->gd, 0, 0, 0, 0, $newW, $newH, imagesx($clone->gd), imagesy($clone->gd));
        imagealphablending($resized, true);

        $clone->gd = $resized;

        return $clone;
    }

    public function crop(Rectangle $region): static
    {
        $clone = clone $this;
        $x = (int) $region->origin->x;
        $y = (int) $region->origin->y;
        $w = max(1, (int) $region->size->width);
        $h = max(1, (int) $region->size->height);

        $cropped = imagecrop($clone->gd, ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h]);
        if ($cropped === false) {
            throw new DriverException('Failed to crop image');
        }

        $clone->gd = $cropped;

        return $clone;
    }

    public function rotate(float $angleDeg, Color $background): static
    {
        $clone = clone $this;
        $bgColor = GdDriver::allocateColor($clone->gd, $background);

        $rotated = imagerotate($clone->gd, -$angleDeg, $bgColor);
        if ($rotated === false) {
            throw new DriverException('Failed to rotate image');
        }

        imagesavealpha($rotated, true);
        $clone->gd = $rotated;

        return $clone;
    }

    public function flip(bool $horizontal = false, bool $vertical = false): static
    {
        $clone = clone $this;

        if ($horizontal && $vertical) {
            imageflip($clone->gd, IMG_FLIP_BOTH);
        } elseif ($horizontal) {
            imageflip($clone->gd, IMG_FLIP_HORIZONTAL);
        } elseif ($vertical) {
            imageflip($clone->gd, IMG_FLIP_VERTICAL);
        }

        return $clone;
    }

    public function apply(Filter $filter): static
    {
        $clone = clone $this;
        $clone->applyFilter($filter);

        return $clone;
    }

    public function effect(Effect $effect): static
    {
        /** @var static */
        return $effect->apply($this);
    }

    public function drawingContext(): DrawingContext
    {
        throw new DriverException('DrawingContext is not yet supported by GdResource');
    }

    private function applyFilter(Filter $filter): void
    {
        match (true) {
            $filter instanceof Grayscale => imagefilter($this->gd, IMG_FILTER_GRAYSCALE),
            $filter instanceof Negate => imagefilter($this->gd, IMG_FILTER_NEGATE),
            $filter instanceof Brightness => imagefilter(
                $this->gd,
                IMG_FILTER_BRIGHTNESS,
                (int) round($filter->level * 2.55),
            ),
            $filter instanceof Contrast => imagefilter(
                $this->gd,
                IMG_FILTER_CONTRAST,
                (int) round(-$filter->level),
            ),
            $filter instanceof Gamma => imagegammacorrect($this->gd, 1.0, $filter->gamma),
            $filter instanceof Sepia => $this->applySepiaFilter($filter->threshold),
            default => throw new DriverException('Filter not supported by GdDriver: ' . $filter::class),
        };
    }

    private function applySepiaFilter(float $threshold): void
    {
        imagefilter($this->gd, IMG_FILTER_GRAYSCALE);
        imagefilter($this->gd, IMG_FILTER_COLORIZE, (int) round($threshold), (int) round($threshold * 0.54), (int) round(-$threshold * 0.29));
    }
}
