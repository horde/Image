<?php

declare(strict_types=1);

namespace Horde\Image\Color;

use InvalidArgumentException;

/**
 * Immutable color value object.
 *
 * Factory methods mirror Horde\Pdf\Color for duck-type compatibility.
 */
final class Color
{
    public static function named(string $name): self
    {
        $color = NamedColors::lookup($name);
        if ($color === null) {
            throw new InvalidArgumentException(sprintf('Unknown color name: "%s"', $name));
        }

        return $color;
    }

    private function __construct(
        private readonly ColorModel $model,
        private readonly float $c1,
        private readonly float $c2,
        private readonly float $c3,
        private readonly float $c4,
    ) {}

    public static function rgb(float $r, float $g, float $b): self
    {
        return new self(ColorModel::Rgb, $r, $g, $b, 0.0);
    }

    public static function rgba(float $r, float $g, float $b, float $a): self
    {
        return new self(ColorModel::Rgba, $r, $g, $b, $a);
    }

    public static function cmyk(float $c, float $m, float $y, float $k): self
    {
        return new self(ColorModel::Cmyk, $c, $m, $y, $k);
    }

    public static function gray(float $g): self
    {
        return new self(ColorModel::Gray, $g, 0.0, 0.0, 0.0);
    }

    public static function hex(string $hex): self
    {
        if (str_starts_with($hex, '#')) {
            $hex = substr($hex, 1);
        }

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        if (strlen($hex) === 8) {
            $a = hexdec(substr($hex, 6, 2)) / 255;
            return new self(ColorModel::Rgba, $r, $g, $b, $a);
        }

        return new self(ColorModel::Rgb, $r, $g, $b, 0.0);
    }

    public function colorModel(): ColorModel
    {
        return $this->model;
    }

    public function red(): float
    {
        return $this->c1;
    }

    public function green(): float
    {
        return $this->c2;
    }

    public function blue(): float
    {
        return $this->c3;
    }

    public function alpha(): float
    {
        return $this->c4;
    }

    public function cyan(): float
    {
        return $this->c1;
    }

    public function magenta(): float
    {
        return $this->c2;
    }

    public function yellow(): float
    {
        return $this->c3;
    }

    public function key(): float
    {
        return $this->c4;
    }

    public function luminance(): float
    {
        return $this->c1;
    }

    public function toHex(): string
    {
        return sprintf(
            '#%02x%02x%02x',
            (int) round($this->c1 * 255),
            (int) round($this->c2 * 255),
            (int) round($this->c3 * 255),
        );
    }

    public function withAlpha(float $alpha): self
    {
        return new self(ColorModel::Rgba, $this->c1, $this->c2, $this->c3, $alpha);
    }

    public function brightness(): float
    {
        return $this->c1 * 0.299 + $this->c2 * 0.587 + $this->c3 * 0.114;
    }

    public function toGray(): self
    {
        return self::gray($this->brightness());
    }

    public function lighten(float $amount): self
    {
        return new self(
            $this->model,
            min($this->c1 + $amount, 1.0),
            min($this->c2 + $amount, 1.0),
            min($this->c3 + $amount, 1.0),
            $this->c4,
        );
    }

    public function darken(float $amount): self
    {
        return new self(
            $this->model,
            max($this->c1 - $amount, 0.0),
            max($this->c2 - $amount, 0.0),
            max($this->c3 - $amount, 0.0),
            $this->c4,
        );
    }

    public function intensify(float $amount): self
    {
        $r = $this->c1;
        $g = $this->c2;
        $b = $this->c3;

        if ($r >= $g && $r >= $b) {
            if ($r === 0.0) {
                return $this;
            }
            $gRatio = $g / $r;
            $bRatio = $b / $r;
            $r = min($r + $amount, 1.0);
            $g = min($gRatio * $r, 1.0);
            $b = min($bRatio * $r, 1.0);
        } elseif ($g >= $r && $g >= $b) {
            if ($g === 0.0) {
                return $this;
            }
            $rRatio = $r / $g;
            $bRatio = $b / $g;
            $g = min($g + $amount, 1.0);
            $r = min($rRatio * $g, 1.0);
            $b = min($bRatio * $g, 1.0);
        } else {
            if ($b === 0.0) {
                return $this;
            }
            $rRatio = $r / $b;
            $gRatio = $g / $b;
            $b = min($b + $amount, 1.0);
            $r = min($rRatio * $b, 1.0);
            $g = min($gRatio * $b, 1.0);
        }

        return new self($this->model, $r, $g, $b, $this->c4);
    }
}
