<?php

declare(strict_types=1);

/**
 * Example: Brush markers and named font sizes.
 *
 * Usage: php8.2 doc/examples/example-brush-fonts.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Horde\Image\Color\Color;
use Horde\Image\Drawing\Brush;
use Horde\Image\Drawing\BrushShape;
use Horde\Image\Drawing\FontSize;
use Horde\Image\Drawing\TextStyle;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Size;
use Horde\Image\ImageFactory;

if (!extension_loaded('imagick')) {
    echo "SKIP: ext-imagick not available\n";
    exit(0);
}

$factory = new ImageFactory(new ImagickDriver());
$canvas = $factory->create(new Size(300.0, 200.0), Color::named('white'));
$ctx = $canvas->drawingContext();

// Draw brush markers in different shapes
$shapes = [
    [BrushShape::Square, 50.0],
    [BrushShape::Circle, 100.0],
    [BrushShape::Diamond, 150.0],
    [BrushShape::Triangle, 200.0],
];

foreach ($shapes as [$shape, $x]) {
    Brush::draw($ctx, new Point($x, 50.0), Color::named('red'), $shape, 8.0);
    echo "Drew {$shape->value} brush at x={$x}\n";
}

// Named font sizes
echo "\nFont sizes:\n";
foreach (FontSize::cases() as $size) {
    echo "  {$size->value}: {$size->points()}pt\n";
}

// Bidirectional lookup
$nearest = FontSize::fromPoints(20.0);
echo "\nNearest to 20pt: {$nearest->value} ({$nearest->points()}pt)\n";

$up = FontSize::nextUp(12.0);
echo "Next up from 12pt: " . ($up ? "{$up->value} ({$up->points()}pt)" : 'none') . "\n";

$down = FontSize::nextDown(18.0);
echo "Next down from 18pt: " . ($down ? "{$down->value} ({$down->points()}pt)" : 'none') . "\n";

// Use FontSize in TextStyle
$ctx->setFillColor(Color::named('black'));
$y = 100.0;
foreach (FontSize::cases() as $size) {
    $ctx->text($size->value, 20.0, $y, new TextStyle(size: $size));
    $y += $size->points() + 4.0;
}

$png = $factory->encode($canvas, ImageFormat::PNG);
echo "\nOutput: " . strlen($png) . " bytes PNG\n";
