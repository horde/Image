<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Format\DecodeOptions;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Size;

interface ImageDriver
{
    public function load(string $data, DecodeOptions $options = new DecodeOptions()): ImageResource;

    public function loadFile(string $path, DecodeOptions $options = new DecodeOptions()): ImageResource;

    public function create(Size $size, Color $background): ImageResource;

    public function encode(ImageResource $image, ImageFormat $format, EncodeOptions $options = new EncodeOptions()): string;

    public function supports(ImageFormat $format): bool;
}
