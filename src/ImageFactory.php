<?php

declare(strict_types=1);

namespace Horde\Image;

use Horde\Image\Driver\ImageDriver;
use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\SequenceDriver;
use Horde\Image\Color\Color;
use Horde\Image\Format\DecodeOptions;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Size;
use Horde\Image\Sequence\AnimationOptions;
use Horde\Image\Sequence\ImageSequence;

/**
 * DI-friendly factory wrapping an ImageDriver.
 */
final class ImageFactory
{
    public function __construct(
        private readonly ImageDriver $driver,
    ) {}

    public function load(string $data, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        return $this->driver->load($data, $options);
    }

    public function loadFile(string $path, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        return $this->driver->loadFile($path, $options);
    }

    public function create(Size $size, Color $background): ImageResource
    {
        return $this->driver->create($size, $background);
    }

    public function encode(ImageResource $image, ImageFormat $format, EncodeOptions $options = new EncodeOptions()): string
    {
        return $this->driver->encode($image, $format, $options);
    }

    public function supports(ImageFormat $format): bool
    {
        return $this->driver->supports($format);
    }

    public function driver(): ImageDriver
    {
        return $this->driver;
    }

    public function loadSequence(string $data, DecodeOptions $options = new DecodeOptions()): ImageSequence
    {
        return $this->sequenceDriver()->loadSequence($data, $options);
    }

    public function loadFileSequence(string $path, DecodeOptions $options = new DecodeOptions()): ImageSequence
    {
        return $this->sequenceDriver()->loadFileSequence($path, $options);
    }

    public function encodeSequence(
        ImageSequence $sequence,
        ImageFormat $format,
        EncodeOptions $options = new EncodeOptions(),
        AnimationOptions $animation = new AnimationOptions(),
    ): string {
        return $this->sequenceDriver()->encodeSequence($sequence, $format, $options, $animation);
    }

    private function sequenceDriver(): SequenceDriver
    {
        if (!$this->driver instanceof SequenceDriver) {
            throw new DriverException('Sequence operations require a SequenceDriver (e.g. ImagickDriver)');
        }

        return $this->driver;
    }
}
