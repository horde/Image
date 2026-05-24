<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Drawing\BlendMode;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\Drawing\LineCap;
use Horde\Image\Drawing\LineDashPattern;
use Horde\Image\Drawing\ShapeStyle;
use Horde\Image\Drawing\TextStyle;
use Horde\Image\DriverException;
use Horde\Image\Geometry\AffineTransform;
use Horde\Image\Geometry\Point;
use Imagick;
use ImagickDraw;
use ImagickPixel;

final class ImagickDrawingContext implements DrawingContext
{
    /** @var array<int, StateEntry> */
    private array $stateStack = [];

    private Color $fillColor;
    private Color $strokeColor;
    private float $lineWidth = 1.0;
    private LineCap $lineCap = LineCap::Butt;
    private LineDashPattern $dashPattern;
    private AffineTransform $transform;

    /** @var array<int, array{string, float[]}> */
    private array $path = [];

    private int $clipCounter = 0;
    private ?string $activeClipPath = null;

    public function __construct(
        private readonly Imagick $imagick,
    ) {
        $this->fillColor = Color::rgb(0.0, 0.0, 0.0);
        $this->strokeColor = Color::rgb(0.0, 0.0, 0.0);
        $this->dashPattern = LineDashPattern::solid();
        $this->transform = AffineTransform::identity();
    }

    public function save(): static
    {
        $this->stateStack[] = new StateEntry(
            fillColor: $this->fillColor,
            strokeColor: $this->strokeColor,
            lineWidth: $this->lineWidth,
            lineCap: $this->lineCap,
            dashPattern: $this->dashPattern,
            transform: $this->transform,
            clipPath: $this->activeClipPath,
        );
        return $this;
    }

    public function restore(): static
    {
        if ($this->stateStack === []) {
            throw new DriverException('Unbalanced restore: no matching save');
        }

        $entry = array_pop($this->stateStack);
        $this->fillColor = $entry->fillColor;
        $this->strokeColor = $entry->strokeColor;
        $this->lineWidth = $entry->lineWidth;
        $this->lineCap = $entry->lineCap;
        $this->dashPattern = $entry->dashPattern;
        $this->transform = $entry->transform;
        $this->activeClipPath = $entry->clipPath;

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
        $this->path[] = ['moveTo', [$x, $y]];
        return $this;
    }

    public function lineTo(float $x, float $y): static
    {
        $this->path[] = ['lineTo', [$x, $y]];
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
        $this->path[] = ['curveTo', [$x1, $y1, $x2, $y2, $x3, $y3]];
        return $this;
    }

    public function closePath(): static
    {
        $this->path[] = ['closePath', []];
        return $this;
    }

    public function rect(float $x, float $y, float $w, float $h): static
    {
        $this->path[] = ['moveTo', [$x, $y]];
        $this->path[] = ['lineTo', [$x + $w, $y]];
        $this->path[] = ['lineTo', [$x + $w, $y + $h]];
        $this->path[] = ['lineTo', [$x, $y + $h]];
        $this->path[] = ['closePath', []];
        return $this;
    }

    public function stroke(): static
    {
        $this->paintPath(stroke: true, fill: false);
        return $this;
    }

    public function fill(): static
    {
        $this->paintPath(stroke: false, fill: true);
        return $this;
    }

    public function fillAndStroke(): static
    {
        $this->paintPath(stroke: true, fill: true);
        return $this;
    }

    public function clip(): static
    {
        $draw = new ImagickDraw();
        $clipId = 'clip_' . (++$this->clipCounter);

        $draw->pushClipPath($clipId);
        $this->replayPath($draw);
        $draw->popClipPath();

        $this->activeClipPath = $clipId;
        $this->path = [];

        return $this;
    }

    public function transform(AffineTransform $transform): static
    {
        $this->transform = $this->transform->multiply($transform);
        return $this;
    }

    // --- Shape helpers (beyond DrawingContext interface) ---

    /**
     * @param ShapeStyle $style How to paint the circle
     */
    public function circle(float $cx, float $cy, float $radius, ShapeStyle $style = ShapeStyle::Fill): static
    {
        return $this->ellipse($cx, $cy, $radius, $radius, $style);
    }

    /**
     * @param ShapeStyle $style How to paint the ellipse
     */
    public function ellipse(float $cx, float $cy, float $rx, float $ry, ShapeStyle $style = ShapeStyle::Fill): static
    {
        $draw = $this->createConfiguredDraw($style);

        [$tcx, $tcy] = $this->applyTransform($cx, $cy);
        $draw->ellipse($tcx, $tcy, $rx, $ry, 0, 360);

        $this->imagick->drawImage($draw);
        return $this;
    }

    /**
     * @param ShapeStyle $style How to paint the arc
     */
    public function arc(
        float $cx,
        float $cy,
        float $radius,
        float $startDeg,
        float $endDeg,
        ShapeStyle $style = ShapeStyle::Stroke,
    ): static {
        $draw = $this->createConfiguredDraw($style);

        [$tcx, $tcy] = $this->applyTransform($cx, $cy);
        $draw->arc(
            $tcx - $radius,
            $tcy - $radius,
            $tcx + $radius,
            $tcy + $radius,
            $startDeg,
            $endDeg,
        );

        $this->imagick->drawImage($draw);
        return $this;
    }

    /**
     * @param Point[] $points
     * @param ShapeStyle $style How to paint the polygon
     */
    public function polygon(array $points, ShapeStyle $style = ShapeStyle::Fill): static
    {
        if (count($points) < 3) {
            throw new DriverException('Polygon requires at least 3 points');
        }

        $draw = $this->createConfiguredDraw($style);

        $coords = [];
        foreach ($points as $point) {
            [$tx, $ty] = $this->applyTransform($point->x, $point->y);
            $coords[] = ['x' => $tx, 'y' => $ty];
        }

        $draw->polygon($coords);
        $this->imagick->drawImage($draw);
        return $this;
    }

    /**
     * @param Point[] $points
     */
    public function polyline(array $points): static
    {
        if (count($points) < 2) {
            throw new DriverException('Polyline requires at least 2 points');
        }

        $draw = $this->createConfiguredDraw(ShapeStyle::Stroke);

        $coords = [];
        foreach ($points as $point) {
            [$tx, $ty] = $this->applyTransform($point->x, $point->y);
            $coords[] = ['x' => $tx, 'y' => $ty];
        }

        $draw->polyline($coords);
        $this->imagick->drawImage($draw);
        return $this;
    }

    /**
     * @param ShapeStyle $style How to paint the rounded rectangle
     */
    public function roundedRect(
        float $x,
        float $y,
        float $w,
        float $h,
        float $radius,
        ShapeStyle $style = ShapeStyle::Fill,
    ): static {
        $draw = $this->createConfiguredDraw($style);

        [$tx1, $ty1] = $this->applyTransform($x, $y);
        [$tx2, $ty2] = $this->applyTransform($x + $w, $y + $h);

        $draw->roundRectangle($tx1, $ty1, $tx2, $ty2, $radius, $radius);
        $this->imagick->drawImage($draw);
        return $this;
    }

    public function text(string $text, float $x, float $y, TextStyle $style = new TextStyle()): static
    {
        $draw = new ImagickDraw();

        $draw->setFillColor(ImagickDriver::colorToPixel($this->fillColor));
        $draw->setFontSize($style->size);

        if ($style->fontFile !== null) {
            $draw->setFont($style->fontFile);
        } elseif ($style->fontFamily !== null) {
            $draw->setFontFamily($style->fontFamily);
        }

        [$tx, $ty] = $this->applyTransform($x, $y);

        if ($this->activeClipPath !== null) {
            $draw->setClipPath($this->activeClipPath);
        }

        $this->imagick->annotateImage($draw, $tx, $ty, $style->angle, $text);
        return $this;
    }

    // --- Private implementation ---

    private function paintPath(bool $stroke, bool $fill): void
    {
        if ($this->path === []) {
            return;
        }

        $draw = new ImagickDraw();

        if ($fill) {
            $draw->setFillColor(ImagickDriver::colorToPixel($this->fillColor));
        } else {
            $draw->setFillColor(new ImagickPixel('none'));
        }

        if ($stroke) {
            $draw->setStrokeColor(ImagickDriver::colorToPixel($this->strokeColor));
            $draw->setStrokeWidth($this->lineWidth);
            $this->applyLineCap($draw);
            $this->applyDashPattern($draw);
        } else {
            $draw->setStrokeColor(new ImagickPixel('none'));
        }

        if ($this->activeClipPath !== null) {
            $draw->setClipPath($this->activeClipPath);
        }

        $this->replayPath($draw);
        $this->imagick->drawImage($draw);
        $this->path = [];
    }

    private function replayPath(ImagickDraw $draw): void
    {
        $draw->pathStart();

        foreach ($this->path as [$op, $args]) {
            match ($op) {
                'moveTo' => (function () use ($draw, $args) {
                    [$tx, $ty] = $this->applyTransform($args[0], $args[1]);
                    $draw->pathMoveToAbsolute($tx, $ty);
                })(),
                'lineTo' => (function () use ($draw, $args) {
                    [$tx, $ty] = $this->applyTransform($args[0], $args[1]);
                    $draw->pathLineToAbsolute($tx, $ty);
                })(),
                'curveTo' => (function () use ($draw, $args) {
                    [$tx1, $ty1] = $this->applyTransform($args[0], $args[1]);
                    [$tx2, $ty2] = $this->applyTransform($args[2], $args[3]);
                    [$tx3, $ty3] = $this->applyTransform($args[4], $args[5]);
                    $draw->pathCurveToAbsolute($tx1, $ty1, $tx2, $ty2, $tx3, $ty3);
                })(),
                'closePath' => $draw->pathClose(),
                default => null,
            };
        }

        $draw->pathFinish();
    }

    private function createConfiguredDraw(ShapeStyle $style): ImagickDraw
    {
        $draw = new ImagickDraw();

        match ($style) {
            ShapeStyle::Fill => (function () use ($draw) {
                $draw->setFillColor(ImagickDriver::colorToPixel($this->fillColor));
                $draw->setStrokeColor(new ImagickPixel('none'));
            })(),
            ShapeStyle::Stroke => (function () use ($draw) {
                $draw->setFillColor(new ImagickPixel('none'));
                $draw->setStrokeColor(ImagickDriver::colorToPixel($this->strokeColor));
                $draw->setStrokeWidth($this->lineWidth);
                $this->applyLineCap($draw);
                $this->applyDashPattern($draw);
            })(),
            ShapeStyle::StrokeAndFill => (function () use ($draw) {
                $draw->setFillColor(ImagickDriver::colorToPixel($this->fillColor));
                $draw->setStrokeColor(ImagickDriver::colorToPixel($this->strokeColor));
                $draw->setStrokeWidth($this->lineWidth);
                $this->applyLineCap($draw);
                $this->applyDashPattern($draw);
            })(),
        };

        if ($this->activeClipPath !== null) {
            $draw->setClipPath($this->activeClipPath);
        }

        return $draw;
    }

    private function applyLineCap(ImagickDraw $draw): void
    {
        $draw->setStrokeLineCap(match ($this->lineCap) {
            LineCap::Butt => Imagick::LINECAP_BUTT,
            LineCap::Round => Imagick::LINECAP_ROUND,
            LineCap::Square => Imagick::LINECAP_SQUARE,
        });
    }

    private function applyDashPattern(ImagickDraw $draw): void
    {
        if ($this->dashPattern->dashArray !== []) {
            $draw->setStrokeDashArray($this->dashPattern->dashArray);
            $draw->setStrokeDashOffset($this->dashPattern->dashPhase);
        }
    }

    /**
     * @return array{float, float}
     */
    private function applyTransform(float $x, float $y): array
    {
        $t = $this->transform;
        return [
            $t->a * $x + $t->c * $y + $t->e,
            $t->b * $x + $t->d * $y + $t->f,
        ];
    }
}
