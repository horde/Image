<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use DOMDocument;
use DOMElement;
use Horde\Image\Color\Color;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\Drawing\LineCap;
use Horde\Image\Drawing\LineDashPattern;
use Horde\Image\Drawing\TextStyle;
use Horde\Image\Geometry\AffineTransform;
use Horde\Image\Geometry\Point;

final class SvgDrawingContext implements DrawingContext
{
    private Color $fillColor;
    private Color $strokeColor;
    private float $lineWidth = 1.0;
    private LineCap $lineCap = LineCap::Butt;
    private LineDashPattern $dashPattern;

    /** @var list<array{Color, Color, float, LineCap, LineDashPattern}> */
    private array $stateStack = [];

    private string $pathData = '';

    public function __construct(
        private readonly DOMDocument $dom,
        private readonly DOMElement $container,
    ) {
        $this->fillColor = Color::rgb(0.0, 0.0, 0.0);
        $this->strokeColor = Color::rgb(0.0, 0.0, 0.0);
        $this->dashPattern = LineDashPattern::solid();
    }

    public function save(): static
    {
        $this->stateStack[] = [
            $this->fillColor,
            $this->strokeColor,
            $this->lineWidth,
            $this->lineCap,
            $this->dashPattern,
        ];
        return $this;
    }

    public function restore(): static
    {
        $state = array_pop($this->stateStack);
        if ($state !== null) {
            [$this->fillColor, $this->strokeColor, $this->lineWidth, $this->lineCap, $this->dashPattern] = $state;
        }
        return $this;
    }

    public function setFillColor(Color $color): static
    {
        $this->fillColor = $color;
        return $this;
    }

    public function setStrokeColor(Color $color): static
    {
        $this->strokeColor = $color;
        return $this;
    }

    public function setLineWidth(float $width): static
    {
        $this->lineWidth = $width;
        return $this;
    }

    public function setLineCap(LineCap $cap): static
    {
        $this->lineCap = $cap;
        return $this;
    }

    public function setDashPattern(LineDashPattern $pattern): static
    {
        $this->dashPattern = $pattern;
        return $this;
    }

    public function moveTo(float $x, float $y): static
    {
        $this->pathData .= sprintf('M%.2f %.2f ', $x, $y);
        return $this;
    }

    public function lineTo(float $x, float $y): static
    {
        $this->pathData .= sprintf('L%.2f %.2f ', $x, $y);
        return $this;
    }

    public function curveTo(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $x3,
        float $y3,
    ): static {
        $this->pathData .= sprintf('C%.2f %.2f %.2f %.2f %.2f %.2f ', $x1, $y1, $x2, $y2, $x3, $y3);
        return $this;
    }

    public function closePath(): static
    {
        $this->pathData .= 'Z ';
        return $this;
    }

    public function rect(float $x, float $y, float $w, float $h): static
    {
        $this->pathData .= sprintf('M%.2f %.2f L%.2f %.2f L%.2f %.2f L%.2f %.2f Z ', $x, $y, $x + $w, $y, $x + $w, $y + $h, $x, $y + $h);
        return $this;
    }

    public function stroke(): static
    {
        $el = $this->createPathElement();
        $el->setAttribute('fill', 'none');
        $el->setAttribute('stroke', SvgResource::colorToSvg($this->strokeColor));
        $el->setAttribute('stroke-width', (string) $this->lineWidth);
        $this->applyLineCap($el);
        $this->applyDash($el);
        $this->container->appendChild($el);
        $this->pathData = '';
        return $this;
    }

    public function fill(): static
    {
        $el = $this->createPathElement();
        $el->setAttribute('fill', SvgResource::colorToSvg($this->fillColor));
        $el->setAttribute('stroke', 'none');
        $this->container->appendChild($el);
        $this->pathData = '';
        return $this;
    }

    public function fillAndStroke(): static
    {
        $el = $this->createPathElement();
        $el->setAttribute('fill', SvgResource::colorToSvg($this->fillColor));
        $el->setAttribute('stroke', SvgResource::colorToSvg($this->strokeColor));
        $el->setAttribute('stroke-width', (string) $this->lineWidth);
        $this->applyLineCap($el);
        $this->applyDash($el);
        $this->container->appendChild($el);
        $this->pathData = '';
        return $this;
    }

    public function clip(): static
    {
        $this->pathData = '';
        return $this;
    }

    public function transform(AffineTransform $transform): static
    {
        return $this;
    }

    public function circle(float $cx, float $cy, float $radius): static
    {
        $el = $this->dom->createElement('circle');
        $el->setAttribute('cx', (string) $cx);
        $el->setAttribute('cy', (string) $cy);
        $el->setAttribute('r', (string) $radius);
        $el->setAttribute('fill', SvgResource::colorToSvg($this->fillColor));
        $el->setAttribute('stroke', SvgResource::colorToSvg($this->strokeColor));
        $el->setAttribute('stroke-width', (string) $this->lineWidth);
        $this->container->appendChild($el);
        return $this;
    }

    public function line(float $x1, float $y1, float $x2, float $y2): static
    {
        $el = $this->dom->createElement('line');
        $el->setAttribute('x1', (string) $x1);
        $el->setAttribute('y1', (string) $y1);
        $el->setAttribute('x2', (string) $x2);
        $el->setAttribute('y2', (string) $y2);
        $el->setAttribute('stroke', SvgResource::colorToSvg($this->strokeColor));
        $el->setAttribute('stroke-width', (string) $this->lineWidth);
        $this->applyLineCap($el);
        $this->applyDash($el);
        $this->container->appendChild($el);
        return $this;
    }

    /** @param array<int, Point> $points */
    public function polygon(array $points): static
    {
        $pairs = [];
        foreach ($points as $pt) {
            $pairs[] = sprintf('%.2f,%.2f', $pt->x, $pt->y);
        }
        $el = $this->dom->createElement('polygon');
        $el->setAttribute('points', implode(' ', $pairs));
        $el->setAttribute('fill', SvgResource::colorToSvg($this->fillColor));
        $el->setAttribute('stroke', SvgResource::colorToSvg($this->strokeColor));
        $el->setAttribute('stroke-width', (string) $this->lineWidth);
        $this->container->appendChild($el);
        return $this;
    }

    public function text(string $text, float $x, float $y, TextStyle $style = new TextStyle()): static
    {
        $el = $this->dom->createElement('text', htmlspecialchars($text, ENT_XML1));
        $el->setAttribute('x', (string) $x);
        $el->setAttribute('y', (string) $y);
        $el->setAttribute('font-size', (string) $style->size);
        $el->setAttribute('fill', SvgResource::colorToSvg($this->fillColor));

        if ($style->fontFamily !== null) {
            $el->setAttribute('font-family', $style->fontFamily);
        }

        if ($style->angle !== 0.0) {
            $el->setAttribute('transform', sprintf('rotate(%.1f %s %s)', $style->angle, (string) $x, (string) $y));
        }

        $this->container->appendChild($el);
        return $this;
    }

    private function createPathElement(): DOMElement
    {
        $el = $this->dom->createElement('path');
        $el->setAttribute('d', trim($this->pathData));
        return $el;
    }

    private function applyLineCap(DOMElement $el): void
    {
        $cap = match ($this->lineCap) {
            LineCap::Butt => 'butt',
            LineCap::Round => 'round',
            LineCap::Square => 'square',
        };
        $el->setAttribute('stroke-linecap', $cap);
    }

    private function applyDash(DOMElement $el): void
    {
        if ($this->dashPattern->dashArray !== []) {
            $el->setAttribute('stroke-dasharray', implode(' ', array_map(fn(float $v) => (string) $v, $this->dashPattern->dashArray)));
            if ($this->dashPattern->dashPhase > 0.0) {
                $el->setAttribute('stroke-dashoffset', (string) $this->dashPattern->dashPhase);
            }
        }
    }
}
