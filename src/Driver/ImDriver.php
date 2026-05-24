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

final class ImDriver implements ImageDriver
{
    private readonly string $convertBin;
    private readonly string $identifyBin;

    public function __construct(
        ?string $convertBin = null,
        ?string $identifyBin = null,
        private readonly string $tmpDir = '',
    ) {
        $this->convertBin = $convertBin ?? $this->findBinary('convert');
        $this->identifyBin = $identifyBin ?? $this->findBinary('identify');
    }

    public function load(string $data, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        $tmpIn = $this->tempFile('im_in_');
        file_put_contents($tmpIn, $data);

        $size = $this->identify($tmpIn);

        return new ImResource($this, $tmpIn, $size, true);
    }

    public function loadFile(string $path, DecodeOptions $options = new DecodeOptions()): ImageResource
    {
        if (!is_readable($path)) {
            throw new DriverException('File not readable: ' . $path);
        }

        $tmpIn = $this->tempFile('im_in_');
        copy($path, $tmpIn);

        $size = $this->identify($tmpIn);

        return new ImResource($this, $tmpIn, $size, true);
    }

    public function create(Size $size, Color $background): ImageResource
    {
        $w = (int) $size->width;
        $h = (int) $size->height;
        $color = $this->colorToImString($background);

        $tmpOut = $this->tempFile('im_create_');
        $cmd = sprintf(
            '%s -size %dx%d xc:%s png:%s',
            escapeshellarg($this->convertBin),
            $w,
            $h,
            escapeshellarg($color),
            escapeshellarg($tmpOut),
        );

        $this->exec($cmd);

        return new ImResource($this, $tmpOut, $size, true);
    }

    public function encode(ImageResource $image, ImageFormat $format, EncodeOptions $options = new EncodeOptions()): string
    {
        if (!$image instanceof ImResource) {
            throw new DriverException('ImDriver can only encode ImResource instances');
        }

        if (!$this->supports($format)) {
            throw new FormatException('Format not supported: ' . $format->value);
        }

        $image->flush();

        $tmpOut = $this->tempFile('im_enc_') . '.' . $format->fileExtension();
        $ops = [];

        if ($options->quality !== null && in_array($format, [ImageFormat::JPEG, ImageFormat::WebP], true)) {
            $ops[] = '-quality ' . $options->quality;
        }

        if ($options->stripMetadata) {
            $ops[] = '-strip';
        }

        if ($options->progressive && $format === ImageFormat::JPEG) {
            $ops[] = '-interlace Plane';
        }

        $cmd = sprintf(
            '%s %s %s %s:%s',
            escapeshellarg($this->convertBin),
            escapeshellarg($image->filePath()),
            implode(' ', $ops),
            escapeshellarg($format->value),
            escapeshellarg($tmpOut),
        );

        $this->exec($cmd);

        $data = file_get_contents($tmpOut);
        @unlink($tmpOut);

        if ($data === false) {
            throw new DriverException('Failed to read encoded output');
        }

        return $data;
    }

    public function supports(ImageFormat $format): bool
    {
        return in_array($format, [
            ImageFormat::PNG,
            ImageFormat::JPEG,
            ImageFormat::GIF,
            ImageFormat::WebP,
            ImageFormat::TIFF,
            ImageFormat::BMP,
        ], true);
    }

    /**
     * @internal
     */
    public function convert(string $inputPath, string $outputPath, string $operations): void
    {
        $cmd = sprintf(
            '%s %s %s %s',
            escapeshellarg($this->convertBin),
            escapeshellarg($inputPath),
            $operations,
            escapeshellarg($outputPath),
        );

        $this->exec($cmd);
    }

    /**
     * @internal
     */
    public function identify(string $path): Size
    {
        $cmd = sprintf(
            '%s -format "%%w %%h" %s',
            escapeshellarg($this->identifyBin),
            escapeshellarg($path . '[0]'),
        );

        $output = $this->exec($cmd);
        $parts = explode(' ', trim($output));

        if (count($parts) < 2) {
            throw new DriverException('Failed to identify image dimensions');
        }

        return new Size((float) $parts[0], (float) $parts[1]);
    }

    /**
     * @internal
     */
    public function colorToImString(Color $color): string
    {
        $r = (int) round($color->red() * 255);
        $g = (int) round($color->green() * 255);
        $b = (int) round($color->blue() * 255);
        $a = $color->alpha();

        if ($a < 0.01 && $color->red() === 0.0 && $color->green() === 0.0 && $color->blue() === 0.0) {
            return 'none';
        }

        if ($a > 0.0) {
            return sprintf('rgba(%d,%d,%d,%.4f)', $r, $g, $b, $a);
        }

        return sprintf('rgb(%d,%d,%d)', $r, $g, $b);
    }

    /**
     * @internal
     */
    public function tempFile(string $prefix = 'im_'): string
    {
        $dir = $this->tmpDir !== '' ? $this->tmpDir : sys_get_temp_dir();
        $path = tempnam($dir, $prefix);

        if ($path === false) {
            throw new DriverException('Failed to create temporary file');
        }

        return $path;
    }

    private function exec(string $cmd): string
    {
        $output = [];
        $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new DriverException(
                sprintf('ImageMagick command failed (exit %d): %s', $exitCode, implode("\n", $output)),
            );
        }

        return implode("\n", $output);
    }

    private function findBinary(string $name): string
    {
        $output = [];
        $exitCode = 0;
        exec('which ' . escapeshellarg($name) . ' 2>/dev/null', $output, $exitCode);

        if ($exitCode === 0 && isset($output[0]) && is_executable($output[0])) {
            return $output[0];
        }

        $commonPaths = [
            '/usr/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/opt/homebrew/bin/' . $name,
        ];

        foreach ($commonPaths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        throw new DriverException(sprintf('ImageMagick binary "%s" not found', $name));
    }
}
