<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\DriverException;
use Horde\Image\Effect\Effect;
use Horde\Image\Filter\Blur;
use Horde\Image\Filter\Brightness;
use Horde\Image\Filter\Colorize;
use Horde\Image\Filter\Contrast;
use Horde\Image\Filter\Filter;
use Horde\Image\Filter\Gamma;
use Horde\Image\Filter\Grayscale;
use Horde\Image\Filter\Modulate;
use Horde\Image\Filter\Negate;
use Horde\Image\Filter\Pixelate;
use Horde\Image\Filter\Sepia;
use Horde\Image\Filter\Sharpen;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use Imagick;
use ImagickPixel;

final class ImagickResource implements ImageResource
{
    public function __construct(
        private Imagick $imagick,
        private readonly ImagickDriver $driver, // @phpstan-ignore property.onlyWritten
    ) {}

    public function size(): Size
    {
        return new Size(
            (float) $this->imagick->getImageWidth(),
            (float) $this->imagick->getImageHeight(),
        );
    }

    public function resize(Size $size): static
    {
        $clone = clone $this;
        $clone->imagick = clone $this->imagick;
        $clone->imagick->resizeImage(
            (int) $size->width,
            (int) $size->height,
            Imagick::FILTER_LANCZOS,
            1,
        );
        return $clone;
    }

    public function crop(Rectangle $region): static
    {
        $clone = clone $this;
        $clone->imagick = clone $this->imagick;
        $clone->imagick->cropImage(
            (int) $region->size->width,
            (int) $region->size->height,
            (int) $region->origin->x,
            (int) $region->origin->y,
        );
        $clone->imagick->setImagePage(0, 0, 0, 0);
        return $clone;
    }

    public function rotate(float $angleDeg, Color $background): static
    {
        $clone = clone $this;
        $clone->imagick = clone $this->imagick;
        $clone->imagick->rotateImage(
            ImagickDriver::colorToPixel($background),
            $angleDeg,
        );
        return $clone;
    }

    public function flip(bool $horizontal = false, bool $vertical = false): static
    {
        $clone = clone $this;
        $clone->imagick = clone $this->imagick;

        if ($vertical) {
            $clone->imagick->flipImage();
        }
        if ($horizontal) {
            $clone->imagick->flopImage();
        }

        return $clone;
    }

    public function apply(Filter $filter): static
    {
        $clone = clone $this;
        $clone->imagick = clone $this->imagick;

        match (true) {
            $filter instanceof Grayscale => $clone->imagick->setImageType(Imagick::IMGTYPE_GRAYSCALE),
            $filter instanceof Sepia => $clone->imagick->sepiaToneImage($filter->threshold),
            $filter instanceof Blur => $clone->imagick->blurImage($filter->radius, $filter->sigma),
            $filter instanceof Sharpen => $clone->imagick->unsharpMaskImage(
                $filter->radius,
                $filter->sigma,
                $filter->amount,
                $filter->threshold,
            ),
            $filter instanceof Brightness => $clone->applyBrightness($filter->level),
            $filter instanceof Contrast => $clone->applyContrast($filter->level),
            $filter instanceof Gamma => $clone->imagick->gammaImage($filter->gamma),
            $filter instanceof Colorize => $clone->imagick->colorizeImage(
                ImagickDriver::colorToPixel($filter->color),
                new ImagickPixel(sprintf('rgba(0,0,0,%.4f)', $filter->opacity)),
            ),
            $filter instanceof Negate => $clone->imagick->negateImage(false),
            $filter instanceof Pixelate => $clone->applyPixelate($filter->size),
            $filter instanceof Modulate => $clone->imagick->modulateImage(
                $filter->brightness,
                $filter->saturation,
                $filter->hue,
            ),
            default => throw new DriverException('Unsupported filter: ' . $filter::class),
        };

        return $clone;
    }

    public function drawingContext(): DrawingContext
    {
        return new ImagickDrawingContext($this->imagick);
    }

    public function effect(Effect $effect): static
    {
        $result = $effect->apply($this);
        if (!$result instanceof static) {
            throw new DriverException('Effect must return an instance of ' . static::class);
        }
        return $result;
    }

    public function imagick(): Imagick
    {
        return $this->imagick;
    }

    public function withImagick(Imagick $imagick): static
    {
        $clone = clone $this;
        $clone->imagick = $imagick;
        return $clone;
    }

    private function applyBrightness(float $level): void
    {
        $brightness = 100 + $level;
        $this->imagick->modulateImage($brightness, 100, 100);
    }

    private function applyContrast(float $level): void
    {
        if ($level > 0) {
            $sharpen = true;
        } else {
            $sharpen = false;
            $level = abs($level);
        }

        $iterations = (int) ceil($level / 10);
        for ($i = 0; $i < $iterations; $i++) {
            $this->imagick->contrastImage($sharpen);
        }
    }

    private function applyPixelate(int $size): void
    {
        $width = $this->imagick->getImageWidth();
        $height = $this->imagick->getImageHeight();

        $this->imagick->scaleImage(
            (int) ceil($width / $size),
            (int) ceil($height / $size),
        );
        $this->imagick->scaleImage($width, $height);
    }
}
