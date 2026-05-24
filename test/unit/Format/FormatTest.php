<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Format;

use Horde\Image\Format\DecodeOptions;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageFormat::class)]
#[CoversClass(EncodeOptions::class)]
#[CoversClass(DecodeOptions::class)]
final class FormatTest extends TestCase
{
    public function testImageFormatMimeTypes(): void
    {
        self::assertSame('image/png', ImageFormat::PNG->mimeType());
        self::assertSame('image/jpeg', ImageFormat::JPEG->mimeType());
        self::assertSame('image/webp', ImageFormat::WebP->mimeType());
        self::assertSame('image/avif', ImageFormat::AVIF->mimeType());
        self::assertSame('image/gif', ImageFormat::GIF->mimeType());
        self::assertSame('image/tiff', ImageFormat::TIFF->mimeType());
        self::assertSame('image/bmp', ImageFormat::BMP->mimeType());
        self::assertSame('image/svg+xml', ImageFormat::SVG->mimeType());
    }

    public function testImageFormatFileExtensions(): void
    {
        self::assertSame('png', ImageFormat::PNG->fileExtension());
        self::assertSame('jpg', ImageFormat::JPEG->fileExtension());
        self::assertSame('webp', ImageFormat::WebP->fileExtension());
        self::assertSame('avif', ImageFormat::AVIF->fileExtension());
        self::assertSame('gif', ImageFormat::GIF->fileExtension());
        self::assertSame('tiff', ImageFormat::TIFF->fileExtension());
        self::assertSame('bmp', ImageFormat::BMP->fileExtension());
        self::assertSame('svg', ImageFormat::SVG->fileExtension());
    }

    public function testImageFormatValues(): void
    {
        self::assertSame('png', ImageFormat::PNG->value);
        self::assertSame('jpeg', ImageFormat::JPEG->value);
        self::assertSame('webp', ImageFormat::WebP->value);
    }

    public function testEncodeOptionsDefaults(): void
    {
        $opts = new EncodeOptions();

        self::assertNull($opts->quality);
        self::assertFalse($opts->progressive);
        self::assertFalse($opts->stripMetadata);
    }

    public function testEncodeOptionsCustom(): void
    {
        $opts = new EncodeOptions(quality: 85, progressive: true, stripMetadata: true);

        self::assertSame(85, $opts->quality);
        self::assertTrue($opts->progressive);
        self::assertTrue($opts->stripMetadata);
    }

    public function testDecodeOptionsDefaults(): void
    {
        $opts = new DecodeOptions();

        self::assertTrue($opts->autoOrient);
        self::assertNull($opts->maxWidth);
        self::assertNull($opts->maxHeight);
    }

    public function testDecodeOptionsCustom(): void
    {
        $opts = new DecodeOptions(autoOrient: false, maxWidth: 1920, maxHeight: 1080);

        self::assertFalse($opts->autoOrient);
        self::assertSame(1920, $opts->maxWidth);
        self::assertSame(1080, $opts->maxHeight);
    }
}
