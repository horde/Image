# Usage Guide

## Choosing a Driver

| Driver | Requirements | Best for |
|--------|-------------|----------|
| `ImagickDriver` | ext-imagick | Full-featured processing, animation, metadata |
| `GdDriver` | ext-gd | Common raster ops without external deps |
| `ImDriver` | ImageMagick CLI (`convert`, `identify`) | Environments without PHP extensions |
| `SvgDriver` | ext-dom (bundled) | Vector graphics generation |
| `PngDriver` | ext-zlib | Zero-dependency PNG fallback |
| `NullDriver` | none | Testing, placeholders, size tracking |

## Creating Images

```php
use Horde\Image\Driver\GdDriver;
use Horde\Image\Color\Color;
use Horde\Image\Geometry\Size;

$driver = new GdDriver();
$image = $driver->create(new Size(640.0, 480.0), Color::rgb(0.2, 0.4, 0.8));
```

## Loading Images

```php
// From binary data
$image = $driver->load(file_get_contents('photo.jpg'));

// From file path
$image = $driver->loadFile('/path/to/photo.jpg');
```

## Encoding/Saving

```php
use Horde\Image\Format\ImageFormat;
use Horde\Image\Format\EncodeOptions;

// Basic encode
$png = $driver->encode($image, ImageFormat::PNG);

// With options
$jpeg = $driver->encode($image, ImageFormat::JPEG, new EncodeOptions(
    quality: 85,
    progressive: true,
    stripMetadata: true,
));
```

## Transformations

All operations are immutable and return new resources:

```php
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;

// Resize
$thumb = $image->resize(new Size(150.0, 150.0));

// Crop
$cropped = $image->crop(new Rectangle(
    new Point(100.0, 50.0),
    new Size(300.0, 200.0),
));

// Rotate (degrees clockwise, with background fill color)
$rotated = $image->rotate(45.0, Color::rgb(1.0, 1.0, 1.0));

// Flip
$mirrored = $image->flip(horizontal: true);
$flipped = $image->flip(vertical: true);
```

## Filters

```php
use Horde\Image\Filter\Grayscale;
use Horde\Image\Filter\Brightness;
use Horde\Image\Filter\Contrast;
use Horde\Image\Filter\Sepia;
use Horde\Image\Filter\Sharpen;

$gray = $image->apply(new Grayscale());
$bright = $image->apply(new Brightness(20.0));
$contrast = $image->apply(new Contrast(10.0));
$vintage = $image->apply(new Sepia(80.0));
$sharp = $image->apply(new Sharpen());
```

## Effects

```php
use Horde\Image\Effect\Border;
use Horde\Image\Effect\DropShadow;
use Horde\Image\Effect\RoundCorners;
use Horde\Image\Effect\TextWatermark;
use Horde\Image\Effect\WatermarkPosition;

$bordered = $image->effect(new Border(
    width: 5.0,
    color: Color::named('black'),
));

$shadowed = $image->effect(new DropShadow(
    offsetX: 4.0,
    offsetY: 4.0,
    blur: 6.0,
));

$rounded = $image->effect(new RoundCorners(radius: 12.0));

$watermarked = $image->effect(new TextWatermark(
    text: '(c) 2026',
    position: WatermarkPosition::BottomRight,
));
```

## Drawing

The DrawingContext uses a path-based API with save/restore state:

```php
use Horde\Image\Drawing\Brush;
use Horde\Image\Drawing\BrushShape;

$ctx = $image->drawingContext();

$ctx->save();
$ctx->setFillColor(Color::rgb(1.0, 0.0, 0.0));
$ctx->setStrokeColor(Color::named('black'));
$ctx->setLineWidth(2.0);
$ctx->rect(10.0, 10.0, 100.0, 50.0);
$ctx->fillAndStroke();
$ctx->restore();

// Brush markers
Brush::draw($ctx, new Point(50.0, 50.0), Color::named('red'), BrushShape::Circle, 6.0);
```

## Named Font Sizes

```php
use Horde\Image\Drawing\FontSize;
use Horde\Image\Drawing\TextStyle;

// Use enum directly
$style = new TextStyle(size: FontSize::Large); // 24pt

// Lookup nearest size from points
$nearest = FontSize::fromPoints(20.0); // FontSize::Medium (18pt)

// Navigate sizes
$bigger = FontSize::nextUp(12.0);  // FontSize::Medium
$smaller = FontSize::nextDown(18.0); // FontSize::Small
```

## Color

```php
use Horde\Image\Color\Color;

// RGB (0.0-1.0 range, fully opaque)
$red = Color::rgb(1.0, 0.0, 0.0);

// RGBA (alpha: 0.0 = transparent, 1.0 = opaque)
$semiRed = Color::rgba(1.0, 0.0, 0.0, 0.5);

// Named CSS colors
$coral = Color::named('coral');
$steel = Color::named('steelblue');

// Utilities
$lighter = $red->lighten(0.2);
$darker = $red->darken(0.3);
$gray = $red->toGray();
$bright = $red->brightness(); // ITU-R BT.601
```

## SVG Generation

```php
use Horde\Image\Driver\SvgDriver;
use Horde\Image\Format\ImageFormat;

$svg = new SvgDriver();
$canvas = $svg->create(new Size(200.0, 200.0), Color::named('white'));

$ctx = $canvas->drawingContext();
$ctx->setFillColor(Color::named('dodgerblue'));
$ctx->moveTo(100.0, 10.0);
$ctx->lineTo(190.0, 190.0);
$ctx->lineTo(10.0, 190.0);
$ctx->closePath();
$ctx->fill();

$output = $svg->encode($canvas, ImageFormat::SVG);
// $output is valid SVG XML
```

## Multi-Frame / Animation (ImagickDriver only)

```php
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Sequence\AnimationOptions;

$driver = new ImagickDriver();
$sequence = $driver->loadFileSequence('animation.gif');

echo count($sequence); // number of frames

foreach ($sequence as $frame) {
    $resource = $frame->resource;
    $delay = $frame->delay;
}

$optimized = $driver->optimize($sequence);
$gif = $driver->encodeSequence($optimized, ImageFormat::GIF, animation: new AnimationOptions(loop: 0));
```

## Using ImageFactory (DI)

```php
use Horde\Image\ImageFactory;
use Horde\Image\Driver\ImagickDriver;

// Wire in your DI container
$factory = new ImageFactory(new ImagickDriver());

$image = $factory->loadFile('photo.jpg');
$thumb = $image->resize(new Size(200.0, 200.0));
$data = $factory->encode($thumb, ImageFormat::WebP);
```

## Metadata Reading

```php
use Horde\Image\Metadata\Reader\BundledReader;

$reader = new BundledReader();
$meta = $reader->read('photo.jpg');

echo $meta->get('DateTime');
echo $meta->get('Make');
echo $meta->get('Model');

$gps = $meta->gps();
if ($gps !== null) {
    echo $gps->latitude;
    echo $gps->longitude;
}
```

## Format Support

Check at runtime what a driver supports:

```php
if ($driver->supports(ImageFormat::WebP)) {
    $data = $driver->encode($image, ImageFormat::WebP);
}
```
