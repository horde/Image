<?php

declare(strict_types=1);

/**
 * Example: SVG generation with SvgDriver.
 *
 * Usage: php8.2 doc/examples/example-svg.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Horde\Image\Color\Color;
use Horde\Image\Drawing\TextStyle;
use Horde\Image\Driver\SvgDriver;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Size;

$driver = new SvgDriver();

// Create SVG canvas
$canvas = $driver->create(new Size(200.0, 200.0), Color::named('white'));
echo "Canvas: {$canvas->size()->width}x{$canvas->size()->height}\n";

// Draw shapes via DrawingContext
$ctx = $canvas->drawingContext();

// Blue filled triangle
$ctx->setFillColor(Color::named('dodgerblue'));
$ctx->moveTo(100.0, 10.0);
$ctx->lineTo(190.0, 190.0);
$ctx->lineTo(10.0, 190.0);
$ctx->closePath();
$ctx->fill();

// Red circle
$ctx->setFillColor(Color::named('red'));
$ctx->setStrokeColor(Color::named('darkred'));
$ctx->setLineWidth(2.0);
$ctx->circle(100.0, 100.0, 30.0);

// Text label
$ctx->setFillColor(Color::named('black'));
$ctx->text('SVG Demo', 60.0, 195.0, new TextStyle(size: 14.0));

// Line
$ctx->setStrokeColor(Color::named('green'));
$ctx->setLineWidth(1.5);
$ctx->line(10.0, 10.0, 190.0, 10.0);

// Polygon
$ctx->setFillColor(Color::named('gold'));
$ctx->polygon([
    new Point(150.0, 50.0),
    new Point(170.0, 80.0),
    new Point(130.0, 80.0),
]);

// Encode to SVG XML
$svg = $driver->encode($canvas, ImageFormat::SVG);
echo "SVG output: " . strlen($svg) . " bytes\n";
echo "Contains <svg>: " . (str_contains($svg, '<svg') ? 'yes' : 'no') . "\n";
echo "Contains <path>: " . (str_contains($svg, '<path') ? 'yes' : 'no') . "\n";
echo "Contains <circle>: " . (str_contains($svg, '<circle') ? 'yes' : 'no') . "\n";
echo "Contains <text>: " . (str_contains($svg, '<text') ? 'yes' : 'no') . "\n";
