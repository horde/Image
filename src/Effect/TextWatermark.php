<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Imagick;
use ImagickDraw;
use ImagickPixel;

final class TextWatermark implements Effect
{
    private readonly Color $resolvedColor;

    public function __construct(
        public readonly string $text,
        public readonly WatermarkPosition $position = WatermarkPosition::BottomRight,
        ?Color $color = null,
        public readonly float $fontSize = 24.0,
        public readonly ?string $fontFamily = null,
        public readonly ?string $fontFile = null,
        public readonly float $angle = 0.0,
        public readonly int $padding = 10,
        public readonly float $opacity = 0.5,
    ) {
        $this->resolvedColor = $color ?? Color::rgb(1.0, 1.0, 1.0);
    }

    public function color(): Color
    {
        return $this->resolvedColor;
    }

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('TextWatermark effect requires ImagickResource');
        }

        $imagick = clone $image->imagick();

        if ($this->position === WatermarkPosition::Tile) {
            $this->applyTiled($imagick);
        } else {
            $this->applyPositioned($imagick);
        }

        return $image->withImagick($imagick);
    }

    private function applyPositioned(Imagick $imagick): void
    {
        $draw = $this->createDraw();
        $draw->setGravity($this->mapGravity($this->position)); // @phpstan-ignore argument.type

        $imagick->annotateImage($draw, $this->padding, $this->padding, $this->angle, $this->text);
    }

    private function applyTiled(Imagick $imagick): void
    {
        $draw = $this->createDraw();

        $metrics = $imagick->queryFontMetrics($draw, $this->text);
        $textWidth = (int) ceil($metrics['textWidth']);
        $textHeight = (int) ceil($metrics['textHeight']);

        $stampWidth = $textWidth + $this->padding * 2;
        $stampHeight = $textHeight + $this->padding * 2;

        $stamp = new Imagick();
        $stamp->newImage($stampWidth, $stampHeight, new ImagickPixel('transparent'));
        $stamp->setImageFormat('png');

        $stampDraw = $this->createDraw();
        $stampDraw->setGravity(Imagick::GRAVITY_CENTER);
        $stamp->annotateImage($stampDraw, 0, 0, $this->angle, $this->text);

        $geo = $imagick->getImageGeometry();
        $imgWidth = $geo['width'];
        $imgHeight = $geo['height'];

        for ($y = 0; $y < $imgHeight; $y += $stampHeight + $this->padding) {
            for ($x = 0; $x < $imgWidth; $x += $stampWidth + $this->padding) {
                $imagick->compositeImage($stamp, Imagick::COMPOSITE_OVER, $x, $y);
            }
        }

        $stamp->destroy();
    }

    private function createDraw(): ImagickDraw
    {
        $draw = new ImagickDraw();

        $pixel = $this->colorToPixel();
        $draw->setFillColor($pixel);
        $draw->setFontSize($this->fontSize);

        if ($this->fontFile !== null) {
            $draw->setFont($this->fontFile);
        } elseif ($this->fontFamily !== null) {
            $draw->setFontFamily($this->fontFamily);
        }

        return $draw;
    }

    private function colorToPixel(): ImagickPixel
    {
        $r = (int) round($this->resolvedColor->red() * 255);
        $g = (int) round($this->resolvedColor->green() * 255);
        $b = (int) round($this->resolvedColor->blue() * 255);
        $a = $this->opacity;

        return new ImagickPixel(sprintf('rgba(%d,%d,%d,%s)', $r, $g, $b, $a));
    }

    private function mapGravity(WatermarkPosition $position): int
    {
        return match ($position) {
            WatermarkPosition::TopLeft => Imagick::GRAVITY_NORTHWEST,
            WatermarkPosition::TopCenter => Imagick::GRAVITY_NORTH,
            WatermarkPosition::TopRight => Imagick::GRAVITY_NORTHEAST,
            WatermarkPosition::CenterLeft => Imagick::GRAVITY_WEST,
            WatermarkPosition::Center => Imagick::GRAVITY_CENTER,
            WatermarkPosition::CenterRight => Imagick::GRAVITY_EAST,
            WatermarkPosition::BottomLeft => Imagick::GRAVITY_SOUTHWEST,
            WatermarkPosition::BottomCenter => Imagick::GRAVITY_SOUTH,
            WatermarkPosition::BottomRight => Imagick::GRAVITY_SOUTHEAST,
            WatermarkPosition::Tile => Imagick::GRAVITY_CENTER,
        };
    }
}
