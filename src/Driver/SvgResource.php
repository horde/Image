<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use DOMDocument;
use DOMElement;
use Horde\Image\Color\Color;
use Horde\Image\Color\ColorModel;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\DriverException;
use Horde\Image\Effect\Effect;
use Horde\Image\Filter\Filter;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;

final class SvgResource implements ImageResource
{
    private DOMDocument $dom;
    private DOMElement $svg;
    private DOMElement $currentGroup;

    public function __construct(
        private Size $size,
        ?Color $background = null,
    ) {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->svg = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'svg');
        $this->svg->setAttribute('width', (string) (int) $size->width);
        $this->svg->setAttribute('height', (string) (int) $size->height);
        $this->svg->setAttribute('viewBox', sprintf('0 0 %d %d', (int) $size->width, (int) $size->height));
        $this->dom->appendChild($this->svg);
        $this->currentGroup = $this->svg;

        if ($background !== null) {
            $isTransparent = $background->colorModel() === ColorModel::Rgba && $background->alpha() < 0.01;
            if (!$isTransparent) {
                $rect = $this->dom->createElement('rect');
                $rect->setAttribute('width', '100%');
                $rect->setAttribute('height', '100%');
                $rect->setAttribute('fill', self::colorToSvg($background));
                $this->svg->appendChild($rect);
            }
        }
    }

    public function size(): Size
    {
        return $this->size;
    }

    public function resize(Size $size): static
    {
        $clone = clone $this;
        $clone->size = $size;
        $clone->svg->setAttribute('width', (string) (int) $size->width);
        $clone->svg->setAttribute('height', (string) (int) $size->height);
        $clone->svg->setAttribute('viewBox', sprintf('0 0 %d %d', (int) $size->width, (int) $size->height));
        return $clone;
    }

    public function crop(Rectangle $region): static
    {
        $clone = clone $this;
        $clone->size = $region->size;
        $clone->svg->setAttribute('width', (string) (int) $region->size->width);
        $clone->svg->setAttribute('height', (string) (int) $region->size->height);
        $clone->svg->setAttribute(
            'viewBox',
            sprintf(
                '%d %d %d %d',
                (int) $region->origin->x,
                (int) $region->origin->y,
                (int) $region->size->width,
                (int) $region->size->height,
            ),
        );
        return $clone;
    }

    public function rotate(float $angleDeg, Color $background): static
    {
        $clone = clone $this;
        $normalized = ((int) $angleDeg) % 360;
        if ($normalized < 0) {
            $normalized += 360;
        }

        if ($normalized === 90 || $normalized === 270) {
            $clone->size = new Size($this->size->height, $this->size->width);
        }

        $g = $clone->dom->createElement('g');
        $cx = (int) $this->size->width / 2;
        $cy = (int) $this->size->height / 2;
        $g->setAttribute('transform', sprintf('rotate(%s %d %d)', (string) $angleDeg, $cx, $cy));

        while ($clone->svg->firstChild) {
            $g->appendChild($clone->svg->firstChild);
        }
        $clone->svg->appendChild($g);

        $clone->svg->setAttribute('width', (string) (int) $clone->size->width);
        $clone->svg->setAttribute('height', (string) (int) $clone->size->height);
        $clone->svg->setAttribute('viewBox', sprintf('0 0 %d %d', (int) $clone->size->width, (int) $clone->size->height));

        return $clone;
    }

    public function flip(bool $horizontal = false, bool $vertical = false): static
    {
        if (!$horizontal && !$vertical) {
            return clone $this;
        }

        $clone = clone $this;
        $transforms = [];

        if ($horizontal) {
            $transforms[] = sprintf('translate(%d, 0) scale(-1, 1)', (int) $this->size->width);
        }
        if ($vertical) {
            $transforms[] = sprintf('translate(0, %d) scale(1, -1)', (int) $this->size->height);
        }

        $g = $clone->dom->createElement('g');
        $g->setAttribute('transform', implode(' ', $transforms));

        while ($clone->svg->firstChild) {
            $g->appendChild($clone->svg->firstChild);
        }
        $clone->svg->appendChild($g);

        return $clone;
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
        return new SvgDrawingContext($this->dom, $this->currentGroup);
    }

    public function toSvg(): string
    {
        $result = $this->dom->saveXML();
        if ($result === false) {
            throw new DriverException('Failed to serialize SVG');
        }
        return $result;
    }

    public function __clone()
    {
        $this->dom = clone $this->dom;
        $root = $this->dom->documentElement;
        if (!$root instanceof DOMElement) {
            throw new DriverException('Failed to clone SVG document');
        }
        $this->svg = $root;
        $this->currentGroup = $this->svg;
    }

    public static function colorToSvg(Color $color): string
    {
        $r = (int) round($color->red() * 255);
        $g = (int) round($color->green() * 255);
        $b = (int) round($color->blue() * 255);

        if ($color->colorModel() === ColorModel::Rgba) {
            $a = $color->alpha();
            return sprintf('rgba(%d,%d,%d,%.3f)', $r, $g, $b, $a);
        }

        return sprintf('rgb(%d,%d,%d)', $r, $g, $b);
    }
}
