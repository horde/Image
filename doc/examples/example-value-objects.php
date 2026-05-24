<?php

declare(strict_types=1);

/**
 * Example: Color and geometry value objects.
 *
 * Usage: php8.2 doc/examples/example-value-objects.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Horde\Image\Color\Color;
use Horde\Image\Geometry\AffineTransform;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;

// RGB color (components 0.0-1.0)
$red = Color::rgb(1.0, 0.0, 0.5);
echo "Color model: " . $red->colorModel()->value . "\n";
echo "Hex: " . $red->toHex() . "\n";

// From hex string
$blue = Color::hex('#3366cc');
echo "Blue red component: " . round($blue->red(), 2) . "\n";

// RGBA with alpha
$semi = Color::rgba(1.0, 1.0, 1.0, 0.5);
echo "Alpha: " . $semi->alpha() . "\n";

// Named CSS colors
$coral = Color::named('coral');
echo "Coral: " . $coral->toHex() . "\n";

// Size with aspect-ratio fitting
$size = new Size(800.0, 600.0);
$fitted = $size->fitWithin(new Size(400.0, 400.0));
echo "Fitted: {$fitted->width}x{$fitted->height}\n";

// Point translation
$point = new Point(10.0, 20.0);
$moved = $point->translate(5.0, -3.0);
echo "Moved: {$moved->x}, {$moved->y}\n";

// Rectangle from coordinates
$rect = Rectangle::fromCoordinates(10.0, 20.0, 110.0, 80.0);
echo "Rect size: {$rect->size->width}x{$rect->size->height}\n";

// Affine transform composition
$rotate = AffineTransform::rotate(90.0);
$translate = AffineTransform::translate(100.0, 0.0);
$combined = $rotate->multiply($translate);
echo "Transform e: {$combined->e}, f: {$combined->f}\n";
