<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\DriverException;
use Horde\Image\Format\DecodeOptions;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Size;

final class NullDriver implements ImageDriver
{
    public function load(string $data, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        $info = @getimagesizefromstring($data);
        if ($info !== false) {
            return new NullResource(new Size((float) $info[0], (float) $info[1]));
        }

        return new NullResource(new Size(1.0, 1.0));
    }

    public function loadFile(string $path, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        if (!is_readable($path)) {
            throw new DriverException('File not readable: ' . $path);
        }

        $info = @getimagesize($path);
        if ($info !== false) {
            return new NullResource(new Size((float) $info[0], (float) $info[1]));
        }

        return new NullResource(new Size(1.0, 1.0));
    }

    public function create(Size $size, Color $background): ImageResource
    {
        return new NullResource($size);
    }

    public function encode(ImageResource $image, ImageFormat $format, EncodeOptions $options = new EncodeOptions()): string
    {
        return '';
    }

    public function supports(ImageFormat $format): bool
    {
        return match ($format) {
            ImageFormat::PNG,
            ImageFormat::JPEG,
            ImageFormat::GIF,
            ImageFormat::WebP,
            ImageFormat::TIFF,
            ImageFormat::BMP,
            ImageFormat::AVIF => true,
            default => false,
        };
    }
}
