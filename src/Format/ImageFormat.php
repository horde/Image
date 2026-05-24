<?php

declare(strict_types=1);

namespace Horde\Image\Format;

enum ImageFormat: string
{
    case PNG = 'png';
    case JPEG = 'jpeg';
    case WebP = 'webp';
    case AVIF = 'avif';
    case GIF = 'gif';
    case TIFF = 'tiff';
    case BMP = 'bmp';
    case SVG = 'svg';

    public function mimeType(): string
    {
        return match ($this) {
            self::PNG => 'image/png',
            self::JPEG => 'image/jpeg',
            self::WebP => 'image/webp',
            self::AVIF => 'image/avif',
            self::GIF => 'image/gif',
            self::TIFF => 'image/tiff',
            self::BMP => 'image/bmp',
            self::SVG => 'image/svg+xml',
        };
    }

    public function fileExtension(): string
    {
        return match ($this) {
            self::JPEG => 'jpg',
            default => $this->value,
        };
    }
}
