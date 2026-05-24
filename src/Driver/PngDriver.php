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

final class PngDriver implements ImageDriver
{
    public function load(string $data, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        $info = @getimagesizefromstring($data);
        if ($info === false) {
            throw new DriverException('Failed to parse image data');
        }

        $gd = @imagecreatefromstring($data);
        if ($gd === false) {
            throw new DriverException('Failed to decode image data');
        }

        $w = imagesx($gd);
        $h = imagesy($gd);
        $resource = new PngResource(new Size((float) $w, (float) $h), Color::rgb(0.0, 0.0, 0.0));

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($gd, $x, $y);
                if ($rgb === false) {
                    continue;
                }
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $resource->setPixel($x, $y, $r, $g, $b);
            }
        }

        imagedestroy($gd);
        return $resource;
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
        return new PngResource($size, $background);
    }

    public function encode(ImageResource $image, ImageFormat $format, EncodeOptions $options = new EncodeOptions()): string
    {
        if (!$image instanceof PngResource) {
            throw new DriverException('PngDriver can only encode PngResource instances');
        }

        if (!$this->supports($format)) {
            throw new FormatException('Format not supported: ' . $format->value);
        }

        return $image->toPngData();
    }

    public function supports(ImageFormat $format): bool
    {
        return $format === ImageFormat::PNG;
    }
}
