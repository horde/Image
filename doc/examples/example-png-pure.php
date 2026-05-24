<?php

declare(strict_types=1);

/**
 * Example: Pure PHP PNG generation without ext-gd or ext-imagick.
 *
 * Usage: php8.2 doc/examples/example-png-pure.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Horde\Image\Color\Color;
use Horde\Image\Driver\PngDriver;
use Horde\Image\Driver\PngResource;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Size;

$driver = new PngDriver();

// Create a small image with a red background
$image = $driver->create(new Size(20.0, 20.0), Color::rgb(1.0, 0.0, 0.0));
assert($image instanceof PngResource);

echo "Size: {$image->size()->width}x{$image->size()->height}\n";

// Set individual pixels (a blue diagonal line)
for ($i = 0; $i < 20; $i++) {
    $image->setPixel($i, $i, 0, 0, 255);
}

// Read a pixel back
$pixel = $image->getPixel(10, 10);
echo "Pixel at (10,10): rgb({$pixel[0]}, {$pixel[1]}, {$pixel[2]})\n";

// Encode to PNG binary
$pngData = $driver->encode($image, ImageFormat::PNG);
echo "PNG magic bytes: " . (str_starts_with($pngData, "\x89PNG") ? 'valid' : 'invalid') . "\n";
echo "PNG size: " . strlen($pngData) . " bytes\n";

// Resize
$resized = $image->resize(new Size(10.0, 10.0));
echo "Resized: {$resized->size()->width}x{$resized->size()->height}\n";

// Rotate 90 degrees
$rotated = $image->rotate(90.0, Color::rgb(0.0, 0.0, 0.0));
echo "Rotated 90: {$rotated->size()->width}x{$rotated->size()->height}\n";

// Flip
$flipped = $image->flip(horizontal: true);
assert($flipped instanceof PngResource);
echo "Flipped pixel at (9,0): rgb(" . implode(', ', $flipped->getPixel(9, 0)) . ")\n";

// Format support
echo "Supports PNG: " . ($driver->supports(ImageFormat::PNG) ? 'yes' : 'no') . "\n";
echo "Supports JPEG: " . ($driver->supports(ImageFormat::JPEG) ? 'yes' : 'no') . "\n";
