<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Imagick;
use ImagickDraw;
use ImagickPixel;

final class PhotoStack implements Effect
{
    private readonly Color $resolvedBackground;
    private readonly Color $resolvedBorderColor;

    /**
     * @param list<ImagickResource> $images Background images (bottom of stack)
     */
    public function __construct(
        public readonly array $images = [],
        public readonly PhotoStackStyle $style = PhotoStackStyle::Plain,
        public readonly int $thumbnailHeight = 150,
        ?Color $background = null,
        ?Color $borderColor = null,
        public readonly int $borderWidth = 1,
        public readonly int $offset = 5,
        public readonly int $padding = 0,
        public readonly int $borderRounding = 10,
    ) {
        $this->resolvedBackground = $background ?? Color::rgba(0.0, 0.0, 0.0, 0.0);
        $this->resolvedBorderColor = $borderColor ?? Color::hex('#333333');
    }

    public function background(): Color
    {
        return $this->resolvedBackground;
    }

    public function borderColor(): Color
    {
        return $this->resolvedBorderColor;
    }

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('PhotoStack effect requires ImagickResource');
        }

        return match ($this->style) {
            PhotoStackStyle::Plain => $this->applyPlain($image),
            PhotoStackStyle::Rounded => $this->applyRounded($image),
            PhotoStackStyle::Polaroid => $this->applyPolaroid($image),
        };
    }

    private function applyPlain(ImagickResource $image): ImagickResource
    {
        $allImages = $this->prepareStack($image);
        $borderPixel = ImagickDriver::colorToPixel($this->resolvedBorderColor);

        foreach ($allImages as $img) {
            $img->borderImage($borderPixel, $this->borderWidth, $this->borderWidth);
        }

        return $this->compositeStack($image, $allImages);
    }

    private function applyRounded(ImagickResource $image): ImagickResource
    {
        $allImages = $this->prepareStack($image);

        foreach ($allImages as $img) {
            $img->roundCorners($this->borderRounding, $this->borderRounding);
            $borderPixel = ImagickDriver::colorToPixel($this->resolvedBorderColor);
            $img->borderImage($borderPixel, $this->borderWidth, $this->borderWidth);
        }

        return $this->compositeStack($image, $allImages);
    }

    private function applyPolaroid(ImagickResource $image): ImagickResource
    {
        $allImages = $this->prepareStack($image);

        $count = count($allImages);
        foreach ($allImages as $i => $img) {
            $img->setImageBackgroundColor(new ImagickPixel('black'));
            $angle = ($i === $count - 1) ? 0.0 : (float) mt_rand(-25, 25);
            $img->polaroidImage(new ImagickDraw(), $angle);
        }

        return $this->compositeStack($image, $allImages);
    }

    /**
     * @return list<Imagick>
     */
    private function prepareStack(ImagickResource $topImage): array
    {
        $result = [];

        foreach ($this->images as $bgImage) {
            $img = clone $bgImage->imagick();
            $img->thumbnailImage(0, $this->thumbnailHeight);
            $result[] = $img;
        }

        $top = clone $topImage->imagick();
        $top->thumbnailImage(0, $this->thumbnailHeight);
        $result[] = $top;

        return $result;
    }

    /**
     * @param list<Imagick> $allImages
     */
    private function compositeStack(ImagickResource $original, array $allImages): ImagickResource
    {
        if ($allImages === []) {
            return $original;
        }

        $maxWidth = 0;
        $maxHeight = 0;
        foreach ($allImages as $img) {
            $geo = $img->getImageGeometry();
            $diagonal = (int) ceil(sqrt($geo['width'] ** 2 + $geo['height'] ** 2));
            if ($diagonal > $maxWidth) {
                $maxWidth = $diagonal;
            }
            if ($diagonal > $maxHeight) {
                $maxHeight = $diagonal;
            }
        }

        $count = count($allImages);
        $canvasWidth = (int) ($maxWidth * 1.5) + ($count * $this->offset) + 20;
        $canvasHeight = (int) ($maxHeight * 1.5) + ($count * $this->offset) + 20;

        $canvas = new Imagick();
        $canvas->newImage($canvasWidth, $canvasHeight, new ImagickPixel('transparent'));
        $canvas->setImageFormat('png');

        $xo = (int) (($canvasWidth - $maxWidth) / 2) + (($count - 1) * $this->offset);
        $yo = (int) (($canvasHeight - $maxHeight) / 2) + (($count - 1) * $this->offset);

        foreach ($allImages as $i => $img) {
            $geo = $img->getImageGeometry();
            $x = $xo - (int) (($geo['width'] - $maxWidth) / 2);
            $y = $yo - (int) (($geo['height'] - $maxHeight) / 2);
            $canvas->compositeImage($img, Imagick::COMPOSITE_OVER, $x, $y);
            $img->destroy();
            $xo -= $this->offset;
            $yo -= $this->offset;
        }

        $canvas->trimImage(0);
        $canvas->setImagePage(0, 0, 0, 0);

        if ($this->padding > 0) {
            $bgPixel = ImagickDriver::colorToPixel($this->resolvedBackground);
            $canvas->borderImage($bgPixel, $this->padding, $this->padding);
        }

        return $original->withImagick($canvas);
    }
}
