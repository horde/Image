<?php

declare(strict_types=1);

/**
 * Example: Applying image filters.
 *
 * Usage: php8.2 doc/examples/example-filters.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Filter\Blur;
use Horde\Image\Filter\Brightness;
use Horde\Image\Filter\Colorize;
use Horde\Image\Filter\Contrast;
use Horde\Image\Filter\Gamma;
use Horde\Image\Filter\Grayscale;
use Horde\Image\Filter\Modulate;
use Horde\Image\Filter\Negate;
use Horde\Image\Filter\Pixelate;
use Horde\Image\Filter\Sepia;
use Horde\Image\Filter\Sharpen;
use Horde\Image\Format\ImageFormat;
use Horde\Image\ImageFactory;

if (!extension_loaded('imagick')) {
    echo "SKIP: ext-imagick not available\n";
    exit(0);
}

$factory = new ImageFactory(new ImagickDriver());

$fixturePath = __DIR__ . '/../../test/Horde/Image/Fixtures/img_exif.jpg';
if (!file_exists($fixturePath)) {
    echo "SKIP: fixture not found\n";
    exit(0);
}

$image = $factory->loadFile($fixturePath);
$size = $image->size();
echo "Original: {$size->width}x{$size->height}\n";

// Individual filters
$gray = $image->apply(new Grayscale());
echo "Grayscale: {$gray->size()->width}x{$gray->size()->height}\n";

$sepia = $image->apply(new Sepia(threshold: 90.0));
echo "Sepia: {$sepia->size()->width}x{$sepia->size()->height}\n";

$blurred = $image->apply(new Blur(sigma: 2.0));
echo "Blur: {$blurred->size()->width}x{$blurred->size()->height}\n";

$sharp = $image->apply(new Sharpen(radius: 0.0, sigma: 1.0, amount: 1.5, threshold: 0.05));
echo "Sharpen: {$sharp->size()->width}x{$sharp->size()->height}\n";

$neg = $image->apply(new Negate());
echo "Negate: {$neg->size()->width}x{$neg->size()->height}\n";

$pix = $image->apply(new Pixelate(size: 8));
echo "Pixelate: {$pix->size()->width}x{$pix->size()->height}\n";

$bright = $image->apply(new Brightness(level: 20.0));
echo "Brightness: {$bright->size()->width}x{$bright->size()->height}\n";

$contr = $image->apply(new Contrast(level: 30.0));
echo "Contrast: {$contr->size()->width}x{$contr->size()->height}\n";

$gamma = $image->apply(new Gamma(gamma: 1.5));
echo "Gamma: {$gamma->size()->width}x{$gamma->size()->height}\n";

$colorized = $image->apply(new Colorize(color: Color::rgb(1.0, 0.0, 0.0), opacity: 0.3));
echo "Colorize: {$colorized->size()->width}x{$colorized->size()->height}\n";

$modulated = $image->apply(new Modulate(brightness: 110.0, saturation: 80.0));
echo "Modulate: {$modulated->size()->width}x{$modulated->size()->height}\n";

// Chaining multiple filters
$chained = $image
    ->apply(new Grayscale())
    ->apply(new Blur(sigma: 1.5))
    ->apply(new Sharpen(amount: 2.0));
$chainedPng = $factory->encode($chained, ImageFormat::PNG);
echo "Chained (grayscale+blur+sharpen): " . strlen($chainedPng) . " bytes\n";
