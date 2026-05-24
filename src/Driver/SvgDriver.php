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
use DOMDocument;

final class SvgDriver implements ImageDriver
{
    public function load(string $data, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        $size = $this->parseSvgSize($data);
        $resource = new SvgResource($size);

        return $this->loadSvgContent($data, $resource);
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
        return new SvgResource($size, $background);
    }

    public function encode(ImageResource $image, ImageFormat $format, EncodeOptions $options = new EncodeOptions()): string
    {
        if (!$image instanceof SvgResource) {
            throw new DriverException('SvgDriver can only encode SvgResource instances');
        }

        if (!$this->supports($format)) {
            throw new FormatException('Format not supported: ' . $format->value);
        }

        return $image->toSvg();
    }

    public function supports(ImageFormat $format): bool
    {
        return $format === ImageFormat::SVG;
    }

    private function parseSvgSize(string $data): Size
    {
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadXML($data);
        libxml_use_internal_errors($prev);

        $svg = $dom->documentElement;
        if ($svg === null) {
            throw new DriverException('Invalid SVG data');
        }

        $width = 0.0;
        $height = 0.0;

        if ($svg->hasAttribute('width') && $svg->hasAttribute('height')) {
            $width = (float) $svg->getAttribute('width');
            $height = (float) $svg->getAttribute('height');
        } elseif ($svg->hasAttribute('viewBox')) {
            $parts = preg_split('/[\s,]+/', $svg->getAttribute('viewBox'));
            if ($parts !== false && count($parts) >= 4) {
                $width = (float) $parts[2];
                $height = (float) $parts[3];
            }
        }

        if ($width <= 0 || $height <= 0) {
            throw new DriverException('Cannot determine SVG dimensions');
        }

        return new Size($width, $height);
    }

    private function loadSvgContent(string $data, SvgResource $fallback): SvgResource
    {
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($data);
        libxml_use_internal_errors($prev);

        if (!$loaded || $dom->documentElement === null) {
            return $fallback;
        }

        $size = $this->parseSvgSize($data);
        return new SvgResource($size);
    }
}
