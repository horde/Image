<?php

declare(strict_types=1);

/**
 * Example: Loading, resizing, cropping and encoding images.
 *
 * Usage: php8.2 doc/examples/example-basic-operations.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;
use Horde\Image\ImageFactory;

if (!extension_loaded('imagick')) {
    echo "SKIP: ext-imagick not available\n";
    exit(0);
}

$driver = new ImagickDriver();
$factory = new ImageFactory($driver);

// Create a canvas
$canvas = $factory->create(new Size(200.0, 150.0), Color::rgb(0.0, 0.5, 1.0));
echo "Canvas: {$canvas->size()->width}x{$canvas->size()->height}\n";

// Load from fixture (if available)
$fixturePath = __DIR__ . '/../../test/Horde/Image/Fixtures/img_exif.jpg';
if (!file_exists($fixturePath)) {
    echo "SKIP: fixture not found\n";
    exit(0);
}

$image = $factory->loadFile($fixturePath);
echo "Loaded: {$image->size()->width}x{$image->size()->height}\n";

// Resize
$resized = $image->resize(new Size(100.0, 100.0));
echo "Resized: {$resized->size()->width}x{$resized->size()->height}\n";

// Crop
$cropped = $image->crop(Rectangle::fromCoordinates(0.0, 0.0, 50.0, 50.0));
echo "Cropped: {$cropped->size()->width}x{$cropped->size()->height}\n";

// Flip
$flipped = $resized->flip(horizontal: true);
echo "Flipped: {$flipped->size()->width}x{$flipped->size()->height}\n";

// Encode as PNG
$pngData = $factory->encode($resized, ImageFormat::PNG);
echo "PNG: " . strlen($pngData) . " bytes\n";

// Encode as WebP with quality
if ($driver->supports(ImageFormat::WebP)) {
    $webpData = $factory->encode($resized, ImageFormat::WebP, new EncodeOptions(quality: 80));
    echo "WebP: " . strlen($webpData) . " bytes\n";
}

// Format support check
echo "Supports PNG: " . ($driver->supports(ImageFormat::PNG) ? 'yes' : 'no') . "\n";
echo "Supports SVG: " . ($driver->supports(ImageFormat::SVG) ? 'yes' : 'no') . "\n";
