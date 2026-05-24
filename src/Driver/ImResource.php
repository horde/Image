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
use Horde\Image\Filter\Modulate;
use Horde\Image\Filter\Negate;
use Horde\Image\Filter\Sepia;
use Horde\Image\Filter\Sharpen;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;

final class ImResource implements ImageResource
{
    /** @var list<string> */
    private array $operations = [];
    private bool $dirty = false;
    private bool $ownsFile;

    public function __construct(
        private readonly ImDriver $driver,
        private string $filePath,
        private Size $size,
        bool $ownsFile = false,
    ) {
        $this->ownsFile = $ownsFile;
    }

    public function __destruct()
    {
        if ($this->ownsFile && file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }

    public function __clone()
    {
        $newPath = $this->driver->tempFile('im_clone_');
        copy($this->filePath, $newPath);
        $this->filePath = $newPath;
        $this->ownsFile = true;
        $this->operations = [];
        $this->dirty = false;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function size(): Size
    {
        if ($this->dirty) {
            $this->flush();
        }

        return $this->size;
    }

    public function resize(Size $size): static
    {
        $clone = clone $this;
        $clone->flush();

        $w = (int) $size->width;
        $h = (int) $size->height;
        $clone->operations[] = sprintf('-resize %dx%d!', $w, $h);
        $clone->size = $size;
        $clone->dirty = true;

        return $clone;
    }

    public function crop(Rectangle $region): static
    {
        $clone = clone $this;
        $clone->flush();

        $w = (int) $region->size->width;
        $h = (int) $region->size->height;
        $x = (int) $region->origin->x;
        $y = (int) $region->origin->y;
        $clone->operations[] = sprintf('-crop %dx%d+%d+%d +repage', $w, $h, $x, $y);
        $clone->size = $region->size;
        $clone->dirty = true;

        return $clone;
    }

    public function rotate(float $angleDeg, Color $background): static
    {
        $clone = clone $this;
        $clone->flush();

        $bgStr = $this->driver->colorToImString($background);
        $clone->operations[] = sprintf('-background %s -rotate %s', escapeshellarg($bgStr), $angleDeg);
        $clone->dirty = true;

        return $clone;
    }

    public function flip(bool $horizontal = false, bool $vertical = false): static
    {
        $clone = clone $this;
        $clone->flush();

        if ($vertical) {
            $clone->operations[] = '-flip';
            $clone->dirty = true;
        }
        if ($horizontal) {
            $clone->operations[] = '-flop';
            $clone->dirty = true;
        }

        return $clone;
    }

    public function apply(Filter $filter): static
    {
        $clone = clone $this;
        $clone->flush();
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
        throw new DriverException('DrawingContext is not supported by ImResource');
    }

    /**
     * @internal Flush pending operations to disk.
     */
    public function flush(): void
    {
        if ($this->operations === []) {
            $this->dirty = false;
            return;
        }

        $ops = implode(' ', $this->operations);
        $tmpOut = $this->driver->tempFile('im_out_');
        $this->driver->convert($this->filePath, $tmpOut, $ops);

        if ($this->ownsFile) {
            @unlink($this->filePath);
        }

        $this->filePath = $tmpOut;
        $this->ownsFile = true;
        $this->operations = [];
        $this->dirty = false;

        $this->size = $this->driver->identify($this->filePath);
    }

    private function applyFilter(Filter $filter): void
    {
        $op = match (true) {
            $filter instanceof Grayscale => '-colorspace Gray',
            $filter instanceof Negate => '-negate',
            $filter instanceof Sepia => sprintf('-sepia-tone %s%%', $filter->threshold),
            $filter instanceof Sharpen => sprintf('-sharpen 0x%s', $filter->sigma),
            $filter instanceof Gamma => sprintf('-gamma %s', $filter->gamma),
            $filter instanceof Brightness => sprintf('-brightness-contrast %sx0', $filter->level),
            $filter instanceof Contrast => sprintf('-brightness-contrast 0x%s', $filter->level),
            $filter instanceof Modulate => sprintf(
                '-modulate %s,%s,%s',
                $filter->brightness,
                $filter->saturation,
                $filter->hue,
            ),
            default => throw new DriverException('Filter not supported by ImDriver: ' . $filter::class),
        };

        $this->operations[] = $op;
        $this->dirty = true;
    }
}
