<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\DriverException;
use Horde\Image\Format\DecodeOptions;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\FormatException;
use Horde\Image\Geometry\Size;
use GdImage;

final class GdDriver implements ImageDriver
{
    public function __construct()
    {
        if (!extension_loaded('gd')) {
            throw new DriverException('ext-gd is required for GdDriver');
        }
    }

    public function load(string $data, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        $gd = @imagecreatefromstring($data);
        if ($gd === false) {
            throw new DriverException('Failed to load image data');
        }

        $this->initGd($gd);

        return new GdResource($gd, $this);
    }

    public function loadFile(string $path, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        if (!is_readable($path)) {
            throw new DriverException('File not readable: ' . $path);
        }

        $data = file_get_contents($path);
        if ($data === false) {
            throw new DriverException('Failed to read file: ' . $path);
        }

        return $this->load($data, $options);
    }

    public function create(Size $size, Color $background): ImageResource
    {
        $w = max(1, (int) $size->width);
        $h = max(1, (int) $size->height);

        $gd = imagecreatetruecolor($w, $h);
        if ($gd === false) {
            throw new DriverException('Failed to create GD image');
        }

        $this->initGd($gd);

        $color = self::allocateColor($gd, $background);
        imagefilledrectangle($gd, 0, 0, $w - 1, $h - 1, $color);

        return new GdResource($gd, $this);
    }

    public function encode(ImageResource $image, ImageFormat $format, EncodeOptions $options = new EncodeOptions()): string
    {
        if (!$image instanceof GdResource) {
            throw new DriverException('GdDriver can only encode GdResource instances');
        }

        if (!$this->supports($format)) {
            throw new FormatException('Format not supported: ' . $format->value);
        }

        ob_start();
        $gd = $image->gd();

        $success = match ($format) {
            ImageFormat::PNG => imagepng($gd, null, -1),
            ImageFormat::JPEG => imagejpeg($gd, null, $options->quality ?? 85),
            ImageFormat::GIF => imagegif($gd),
            ImageFormat::WebP => imagewebp($gd, null, $options->quality ?? 80),
            ImageFormat::BMP => imagebmp($gd),
            ImageFormat::AVIF => function_exists('imageavif') && imageavif($gd, null, $options->quality ?? 80),
            default => false,
        };

        $data = ob_get_clean();

        if (!$success || $data === false || $data === '') {
            throw new DriverException('Failed to encode image as ' . $format->value);
        }

        return $data;
    }

    public function supports(ImageFormat $format): bool
    {
        return match ($format) {
            ImageFormat::PNG, ImageFormat::JPEG, ImageFormat::GIF, ImageFormat::BMP => true,
            ImageFormat::WebP => function_exists('imagewebp'),
            ImageFormat::AVIF => function_exists('imageavif'),
            default => false,
        };
    }

    public static function allocateColor(GdImage $gd, Color $color): int
    {
        $r = min(255, max(0, (int) round($color->red() * 255)));
        $g = min(255, max(0, (int) round($color->green() * 255)));
        $b = min(255, max(0, (int) round($color->blue() * 255)));
        $a = min(127, max(0, 127 - (int) round($color->alpha() * 127)));

        $result = imagecolorallocatealpha($gd, $r, $g, $b, $a);
        if ($result === false) {
            return 0;
        }

        return $result;
    }

    private function initGd(GdImage $gd): void
    {
        imagesavealpha($gd, true);
        imagealphablending($gd, true);
    }
}
