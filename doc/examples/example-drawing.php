<?php

declare(strict_types=1);

/**
 * Example: DrawingContext path operations and shape helpers.
 *
 * Usage: php8.2 doc/examples/example-drawing.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Horde\Image\Color\Color;
use Horde\Image\Drawing\LineCap;
use Horde\Image\Drawing\LineDashPattern;
use Horde\Image\Drawing\ShapeStyle;
use Horde\Image\Drawing\TextStyle;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\AffineTransform;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Size;
use Horde\Image\ImageFactory;

if (!extension_loaded('imagick')) {
    echo "SKIP: ext-imagick not available\n";
    exit(0);
}

$factory = new ImageFactory(new ImagickDriver());
$canvas = $factory->create(new Size(200.0, 200.0), Color::rgb(1.0, 1.0, 1.0));
$ctx = $canvas->drawingContext();

// Filled path (red rectangle)
$ctx->setFillColor(Color::rgb(1.0, 0.0, 0.0))
    ->moveTo(10.0, 10.0)
    ->lineTo(90.0, 10.0)
    ->lineTo(90.0, 90.0)
    ->lineTo(10.0, 90.0)
    ->closePath()
    ->fill();

// Stroked rectangle
$ctx->setStrokeColor(Color::rgb(0.0, 0.0, 1.0))
    ->setLineWidth(3.0)
    ->rect(100.0, 100.0, 80.0, 80.0)
    ->stroke();

// Circle
$ctx->setFillColor(Color::rgb(0.0, 1.0, 0.0))
    ->circle(50.0, 150.0, 20.0, ShapeStyle::Fill);

// Ellipse with stroke and fill
$ctx->setFillColor(Color::hex('#ff9900'))
    ->setStrokeColor(Color::rgb(0.0, 0.0, 0.0))
    ->setLineWidth(2.0)
    ->ellipse(150.0, 50.0, 30.0, 20.0, ShapeStyle::StrokeAndFill);

// Polygon (triangle)
$ctx->setFillColor(Color::rgb(0.5, 0.0, 0.5))
    ->polygon([
        new Point(120.0, 150.0),
        new Point(180.0, 150.0),
        new Point(150.0, 190.0),
    ], ShapeStyle::Fill);

// Polyline (open path)
$ctx->setStrokeColor(Color::rgb(0.0, 0.5, 0.5))
    ->setLineWidth(2.0)
    ->polyline([
        new Point(10.0, 195.0),
        new Point(50.0, 180.0),
        new Point(90.0, 195.0),
    ]);

// Rounded rectangle
$ctx->setFillColor(Color::rgba(0.0, 0.0, 1.0, 0.5))
    ->roundedRect(5.0, 100.0, 60.0, 40.0, 8.0, ShapeStyle::Fill);

// Text
$ctx->setFillColor(Color::rgb(0.0, 0.0, 0.0))
    ->text('Hello', 100.0, 30.0, new TextStyle(size: 16.0));

// Dashed line with round caps
$ctx->setStrokeColor(Color::rgb(0.5, 0.5, 0.5))
    ->setDashPattern(new LineDashPattern([5.0, 3.0]))
    ->setLineCap(LineCap::Round)
    ->moveTo(5.0, 5.0)
    ->lineTo(195.0, 5.0)
    ->stroke();

// Save/restore state
$ctx->save()
    ->setFillColor(Color::rgb(1.0, 1.0, 0.0))
    ->setLineWidth(5.0)
    ->restore();

// Transform
$ctx->save()
    ->transform(AffineTransform::translate(100.0, 100.0))
    ->setFillColor(Color::rgb(1.0, 0.0, 1.0))
    ->circle(0.0, 0.0, 5.0, ShapeStyle::Fill)
    ->restore();

// Arc
$ctx->setStrokeColor(Color::rgb(0.8, 0.2, 0.0))
    ->setLineWidth(2.0)
    ->arc(150.0, 150.0, 15.0, 0.0, 270.0, ShapeStyle::Stroke);

// Encode result
$png = $factory->encode($canvas, ImageFormat::PNG);
echo "Drawing canvas: " . strlen($png) . " bytes PNG\n";
