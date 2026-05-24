<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Color\ColorModel;
use Horde\Image\DriverException;
use Horde\Image\Format\DecodeOptions;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\FormatException;
use Horde\Image\Geometry\Size;
use Horde\Image\Sequence\AnimationOptions;
use Horde\Image\Sequence\DisposalMethod;
use Horde\Image\Sequence\Frame;
use Horde\Image\Sequence\ImageSequence;
use Imagick;
use ImagickPixel;
use ImagickException;

final class ImagickDriver implements SequenceDriver
{
    public function __construct()
    {
        if (!extension_loaded('imagick')) {
            throw new DriverException('ext-imagick is required for ImagickDriver');
        }
    }

    public function load(string $data, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        $imagick = new Imagick();

        try {
            $imagick->readImageBlob($data);
        } catch (ImagickException $e) {
            throw new DriverException('Failed to load image data: ' . $e->getMessage(), 0, $e);
        }

        return $this->postProcess($imagick, $options);
    }

    public function loadFile(string $path, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        if (!is_readable($path)) {
            throw new DriverException('File not readable: ' . $path);
        }

        $imagick = new Imagick();

        try {
            $imagick->readImage($path);
        } catch (ImagickException $e) {
            throw new DriverException('Failed to load file: ' . $e->getMessage(), 0, $e);
        }

        return $this->postProcess($imagick, $options);
    }

    public function create(Size $size, Color $background): ImageResource
    {
        $imagick = new Imagick();
        $pixel = self::colorToPixel($background);

        $imagick->newImage((int) $size->width, (int) $size->height, $pixel);
        $imagick->setImageFormat('png');

        return new ImagickResource($imagick, $this);
    }

    public function encode(ImageResource $image, ImageFormat $format, EncodeOptions $options = new EncodeOptions()): string
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('ImagickDriver can only encode ImagickResource instances');
        }

        if (!$this->supports($format)) {
            throw new FormatException('Format not supported: ' . $format->value);
        }

        $imagick = clone $image->imagick();
        $imagick->setImageFormat($this->formatToImagick($format));

        if ($options->quality !== null) {
            $imagick->setImageCompressionQuality($options->quality);
        }

        if ($options->progressive && in_array($format, [ImageFormat::JPEG, ImageFormat::PNG], true)) {
            $imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
        }

        if ($options->stripMetadata) {
            $imagick->stripImage();
        }

        return $imagick->getImageBlob();
    }

    public function supports(ImageFormat $format): bool
    {
        $map = $this->formatToImagick($format);
        $supported = Imagick::queryFormats($map);
        return $supported !== [];
    }

    public function loadSequence(string $data, DecodeOptions $options = new DecodeOptions()): ImageSequence
    {
        $imagick = new Imagick();

        try {
            $imagick->readImageBlob($data);
        } catch (ImagickException $e) {
            throw new DriverException('Failed to load image data: ' . $e->getMessage(), 0, $e);
        }

        return $this->buildSequence($imagick, $options);
    }

    public function loadFileSequence(string $path, DecodeOptions $options = new DecodeOptions()): ImageSequence
    {
        if (!is_readable($path)) {
            throw new DriverException('File not readable: ' . $path);
        }

        $imagick = new Imagick();

        try {
            $imagick->readImage($path);
        } catch (ImagickException $e) {
            throw new DriverException('Failed to load file: ' . $e->getMessage(), 0, $e);
        }

        return $this->buildSequence($imagick, $options);
    }

    public function encodeSequence(
        ImageSequence $sequence,
        ImageFormat $format,
        EncodeOptions $options = new EncodeOptions(),
        AnimationOptions $animation = new AnimationOptions(),
    ): string {
        if (!$this->supports($format)) {
            throw new FormatException('Format not supported: ' . $format->value);
        }

        $imagick = new Imagick();
        $formatStr = $this->formatToImagick($format);

        foreach ($sequence->frames() as $frame) {
            if (!$frame->image instanceof ImagickResource) {
                throw new DriverException('All frames must be ImagickResource instances');
            }

            $frameIm = clone $frame->image->imagick();
            $frameIm->setImageFormat($formatStr);

            $delay = $frame->delay > 0 ? $frame->delay : $animation->defaultDelay;
            $frameIm->setImageDelay((int) round($delay / 10));
            $frameIm->setImageDispose($frame->disposal->value);
            $frameIm->setImagePage(
                (int) $sequence->canvasSize()->width,
                (int) $sequence->canvasSize()->height,
                $frame->x,
                $frame->y,
            );

            if ($options->quality !== null) {
                $frameIm->setImageCompressionQuality($options->quality);
            }

            $imagick->addImage($frameIm);
        }

        $imagick->setImageIterations($animation->loopCount);

        if ($animation->optimize && $sequence->count() > 1) {
            $optimized = $imagick->deconstructImages();
            $imagick->clear();
            $imagick = $optimized;
        }

        if ($options->stripMetadata) {
            $imagick->stripImage();
        }

        return $imagick->getImagesBlob();
    }

    public function coalesce(ImageSequence $sequence): ImageSequence
    {
        $combined = $this->sequenceToImagick($sequence);
        $coalesced = $combined->coalesceImages();

        return $this->imagickToSequence($coalesced);
    }

    public function optimize(ImageSequence $sequence): ImageSequence
    {
        $combined = $this->sequenceToImagick($sequence);
        $optimized = $combined->deconstructImages();

        return $this->imagickToSequence($optimized);
    }

    private function buildSequence(Imagick $imagick, DecodeOptions $options): ImageSequence
    {
        $numImages = $imagick->getNumberImages();
        $frames = [];

        $imagick->setFirstIterator();
        $canvasWidth = $imagick->getImageWidth();
        $canvasHeight = $imagick->getImageHeight();

        for ($i = 0; $i < $numImages; $i++) {
            $imagick->setIteratorIndex($i);

            $frameIm = clone $imagick;
            $frameIm->setIteratorIndex(0);

            if ($options->autoOrient) {
                $frameIm->autoOrient();
            }

            $delay = $imagick->getImageDelay() * 10;
            $dispose = DisposalMethod::tryFrom($imagick->getImageDispose()) ?? DisposalMethod::None;
            $page = $imagick->getImagePage();

            $resource = new ImagickResource($frameIm, $this);
            $frames[] = new Frame(
                image: $resource,
                delay: $delay,
                disposal: $dispose,
                x: $page['x'],
                y: $page['y'],
            );
        }

        $canvasSize = new Size((float) $canvasWidth, (float) $canvasHeight);

        return ImageSequence::fromFrames($frames, $canvasSize);
    }

    private function sequenceToImagick(ImageSequence $sequence): Imagick
    {
        $combined = new Imagick();

        foreach ($sequence->frames() as $frame) {
            if (!$frame->image instanceof ImagickResource) {
                throw new DriverException('All frames must be ImagickResource instances');
            }
            $combined->addImage(clone $frame->image->imagick());
        }

        return $combined;
    }

    private function imagickToSequence(Imagick $imagick): ImageSequence
    {
        $frames = [];
        $numImages = $imagick->getNumberImages();

        $imagick->setFirstIterator();
        $canvasWidth = $imagick->getImageWidth();
        $canvasHeight = $imagick->getImageHeight();

        for ($i = 0; $i < $numImages; $i++) {
            $imagick->setIteratorIndex($i);

            $frameIm = clone $imagick;
            $frameIm->setIteratorIndex(0);

            $delay = $imagick->getImageDelay() * 10;
            $dispose = DisposalMethod::tryFrom($imagick->getImageDispose()) ?? DisposalMethod::None;
            $page = $imagick->getImagePage();

            $resource = new ImagickResource($frameIm, $this);
            $frames[] = new Frame(
                image: $resource,
                delay: $delay,
                disposal: $dispose,
                x: $page['x'],
                y: $page['y'],
            );
        }

        return ImageSequence::fromFrames($frames, new Size((float) $canvasWidth, (float) $canvasHeight));
    }

    private function postProcess(Imagick $imagick, DecodeOptions $options): ImagickResource
    {
        if ($options->autoOrient) {
            $imagick->autoOrient();
        }

        if ($options->maxWidth !== null || $options->maxHeight !== null) {
            $w = $imagick->getImageWidth();
            $h = $imagick->getImageHeight();
            $maxW = $options->maxWidth ?? $w;
            $maxH = $options->maxHeight ?? $h;

            if ($w > $maxW || $h > $maxH) {
                $imagick->thumbnailImage($maxW, $maxH, true);
            }
        }

        return new ImagickResource($imagick, $this);
    }

    private function formatToImagick(ImageFormat $format): string
    {
        return match ($format) {
            ImageFormat::PNG => 'PNG',
            ImageFormat::JPEG => 'JPEG',
            ImageFormat::WebP => 'WEBP',
            ImageFormat::AVIF => 'AVIF',
            ImageFormat::GIF => 'GIF',
            ImageFormat::TIFF => 'TIFF',
            ImageFormat::BMP => 'BMP',
            ImageFormat::SVG => 'SVG',
        };
    }

    public static function colorToPixel(Color $color): ImagickPixel
    {
        return match ($color->colorModel()) {
            ColorModel::Rgb => new ImagickPixel(sprintf(
                'rgb(%d, %d, %d)',
                (int) round($color->red() * 255),
                (int) round($color->green() * 255),
                (int) round($color->blue() * 255),
            )),
            ColorModel::Rgba => new ImagickPixel(sprintf(
                'rgba(%d, %d, %d, %.4f)',
                (int) round($color->red() * 255),
                (int) round($color->green() * 255),
                (int) round($color->blue() * 255),
                $color->alpha(),
            )),
            ColorModel::Cmyk => new ImagickPixel(sprintf(
                'cmyk(%d%%, %d%%, %d%%, %d%%)',
                (int) round($color->cyan() * 100),
                (int) round($color->magenta() * 100),
                (int) round($color->yellow() * 100),
                (int) round($color->key() * 100),
            )),
            ColorModel::Gray => new ImagickPixel(sprintf(
                'gray(%d%%)',
                (int) round($color->luminance() * 100),
            )),
        };
    }
}
