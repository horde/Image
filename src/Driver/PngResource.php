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

final class PngResource implements ImageResource
{
    /** @var array<int, array<int, array{int, int, int}>> row => col => [r, g, b] */
    private array $pixels;
    private int $width;
    private int $height;

    public function __construct(Size $size, Color $background)
    {
        $this->width = max(1, (int) $size->width);
        $this->height = max(1, (int) $size->height);

        $r = (int) round($background->red() * 255);
        $g = (int) round($background->green() * 255);
        $b = (int) round($background->blue() * 255);

        $row = array_fill(0, $this->width, [$r, $g, $b]);
        $this->pixels = array_fill(0, $this->height, $row);
    }

    /** @param array<int, array<int, array{int, int, int}>> $pixels */
    private static function fromPixels(array $pixels, int $width, int $height): self
    {
        $size = new Size((float) $width, (float) $height);
        $instance = new self($size, Color::rgb(0.0, 0.0, 0.0));
        $instance->pixels = $pixels;
        $instance->width = $width;
        $instance->height = $height;
        return $instance;
    }

    public function size(): Size
    {
        return new Size((float) $this->width, (float) $this->height);
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function setPixel(int $x, int $y, int $r, int $g, int $b): void
    {
        if ($x >= 0 && $x < $this->width && $y >= 0 && $y < $this->height) {
            $this->pixels[$y][$x] = [$r, $g, $b];
        }
    }

    /** @return array{int, int, int} */
    public function getPixel(int $x, int $y): array
    {
        if ($x >= 0 && $x < $this->width && $y >= 0 && $y < $this->height) {
            return $this->pixels[$y][$x];
        }
        return [0, 0, 0];
    }

    public function resize(Size $size): static
    {
        $newW = max(1, (int) $size->width);
        $newH = max(1, (int) $size->height);
        $pixels = [];

        for ($y = 0; $y < $newH; $y++) {
            $srcY = (int) ($y * $this->height / $newH);
            $row = [];
            for ($x = 0; $x < $newW; $x++) {
                $srcX = (int) ($x * $this->width / $newW);
                $row[] = $this->pixels[$srcY][$srcX];
            }
            $pixels[] = $row;
        }

        return self::fromPixels($pixels, $newW, $newH);
    }

    public function crop(Rectangle $region): static
    {
        $sx = max(0, (int) $region->origin->x);
        $sy = max(0, (int) $region->origin->y);
        $w = (int) $region->size->width;
        $h = (int) $region->size->height;
        $pixels = [];

        for ($y = 0; $y < $h; $y++) {
            $row = [];
            for ($x = 0; $x < $w; $x++) {
                $row[] = $this->getPixel($sx + $x, $sy + $y);
            }
            $pixels[] = $row;
        }

        return self::fromPixels($pixels, $w, $h);
    }

    public function rotate(float $angleDeg, Color $background): static
    {
        $normalized = ((int) $angleDeg) % 360;
        if ($normalized < 0) {
            $normalized += 360;
        }

        if ($normalized === 0) {
            return clone $this;
        }

        if ($normalized === 90) {
            $pixels = [];
            for ($y = 0; $y < $this->width; $y++) {
                $row = [];
                for ($x = 0; $x < $this->height; $x++) {
                    $row[] = $this->pixels[$this->height - 1 - $x][$y];
                }
                $pixels[] = $row;
            }
            return self::fromPixels($pixels, $this->height, $this->width);
        }

        if ($normalized === 180) {
            $pixels = [];
            for ($y = $this->height - 1; $y >= 0; $y--) {
                $pixels[] = array_reverse($this->pixels[$y]);
            }
            return self::fromPixels($pixels, $this->width, $this->height);
        }

        if ($normalized === 270) {
            $pixels = [];
            for ($y = $this->width - 1; $y >= 0; $y--) {
                $row = [];
                for ($x = 0; $x < $this->height; $x++) {
                    $row[] = $this->pixels[$x][$y];
                }
                $pixels[] = $row;
            }
            return self::fromPixels($pixels, $this->height, $this->width);
        }

        return clone $this;
    }

    public function flip(bool $horizontal = false, bool $vertical = false): static
    {
        $pixels = $this->pixels;

        if ($vertical) {
            $pixels = array_reverse($pixels);
        }

        if ($horizontal) {
            foreach ($pixels as $y => $row) {
                $pixels[$y] = array_reverse($row);
            }
        }

        if (!$horizontal && !$vertical) {
            $pixels = $this->pixels;
        }

        return self::fromPixels($pixels, $this->width, $this->height);
    }

    public function apply(Filter $filter): static
    {
        return clone $this;
    }

    public function effect(Effect $effect): static
    {
        return clone $this;
    }

    public function drawingContext(): DrawingContext
    {
        throw new DriverException('PngDriver does not support a full drawing context');
    }

    public function toPngData(): string
    {
        $ihdr = $this->buildIhdr();
        $idat = $this->buildIdat();
        $iend = $this->buildIend();

        return "\x89PNG\r\n\x1a\n" . $ihdr . $idat . $iend;
    }

    private function buildIhdr(): string
    {
        $data = pack('NNCCCCC', $this->width, $this->height, 8, 2, 0, 0, 0);
        return $this->buildChunk('IHDR', $data);
    }

    private function buildIdat(): string
    {
        $raw = '';
        for ($y = 0; $y < $this->height; $y++) {
            $raw .= "\x00";
            for ($x = 0; $x < $this->width; $x++) {
                [$r, $g, $b] = $this->pixels[$y][$x];
                $raw .= chr($r) . chr($g) . chr($b);
            }
        }

        $compressed = gzcompress($raw);
        if ($compressed === false) {
            throw new DriverException('Failed to compress PNG data');
        }

        return $this->buildChunk('IDAT', $compressed);
    }

    private function buildIend(): string
    {
        return $this->buildChunk('IEND', '');
    }

    private function buildChunk(string $type, string $data): string
    {
        $chunk = $type . $data;
        $crc = pack('N', crc32($chunk));
        $length = pack('N', strlen($data));
        return $length . $chunk . $crc;
    }
}
