# Upgrading from Horde_Image (lib/) to Horde\Image (src/)

## Overview

The new `Horde\Image` API under `src/` coexists with the legacy `Horde_Image` classes in `lib/`. Both autoloaders work simultaneously and so migration can be incremental.

## Namespace Change

| Legacy | Modern |
|--------|--------|
| `Horde_Image_Base` | `Horde\Image\Driver\ImageDriver` (interface) |
| `Horde_Image_Imagick` | `Horde\Image\Driver\ImagickDriver` |
| `Horde_Image_Gd` | `Horde\Image\Driver\GdDriver` |
| `Horde_Image_Im` | `Horde\Image\Driver\ImDriver` |
| `Horde_Image_Svg` | `Horde\Image\Driver\SvgDriver` |
| `Horde_Image_Png` | `Horde\Image\Driver\PngDriver` |
| `Horde_Image_Null` | `Horde\Image\Driver\NullDriver` |
| `Horde_Image_Color` | `Horde\Image\Color\Color` |
| `Horde_Image_Exif` | `Horde\Image\Metadata\Reader\*` |
| `Horde_Image_Effect_*` | `Horde\Image\Effect\*` |

## Architecture Changes

### Driver/Resource Split

Legacy had a single object per image that was both driver and resource:

```php
// Legacy
$image = new Horde_Image_Imagick(['width' => 100, 'height' => 80]);
$image->resize(50, 40);
$data = $image->raw();
```

Modern separates the driver (stateless factory) from the resource (image data):

```php
// Modern
$driver = new ImagickDriver();
$image = $driver->create(new Size(100.0, 80.0), Color::named('white'));
$resized = $image->resize(new Size(50.0, 40.0));
$data = $driver->encode($resized, ImageFormat::PNG);
```

### Immutability

Legacy mutated images in place. Modern returns new instances:

```php
// Legacy (mutates $image)
$image->resize(50, 40);
$image->flip();

// Modern (returns new resources)
$resized = $image->resize(new Size(50.0, 40.0));
$flipped = $resized->flip(horizontal: true);
// $image is unchanged
```

### Interface Segregation

Legacy had one large abstract class. Modern splits capabilities:

- `ImageDriver` - single-image operations (all backends)
- `SequenceDriver extends ImageDriver` - animation/multi-frame (ImagickDriver only)
- `ImageResource` - per-image operations (resize, crop, rotate, flip, filter, effect)
- `DrawingContext` - path-based 2D drawing

### Color

Legacy used hex strings and arrays. Modern uses a typed value object:

```php
// Legacy
$image->rectangle(0, 0, 100, 100, 'red', '#ff0000');

// Modern
$ctx = $image->drawingContext();
$ctx->setFillColor(Color::named('red'));
$ctx->rect(0.0, 0.0, 100.0, 100.0);
$ctx->fill();
```

### Font Sizes

Legacy used string names passed to methods. Modern uses a typed enum:

```php
// Legacy
$image->text('Hello', 10, 20, 'arial', 'large');

// Modern
use Horde\Image\Drawing\FontSize;
use Horde\Image\Drawing\TextStyle;

$ctx->text('Hello', 10.0, 20.0, new TextStyle(size: FontSize::Large));
```

### Effects

Legacy used string-keyed parameter arrays. Modern uses typed objects:

```php
// Legacy
$image->addEffect('border', ['bordercolor' => '#000', 'borderwidth' => 2]);

// Modern
$bordered = $image->effect(new Border(width: 2.0, color: Color::named('black')));
```

### Metadata

Legacy had a single `Horde_Image_Exif` class. Modern has pluggable readers:

```php
// Legacy
$exif = new Horde_Image_Exif(new Horde_Image_Exif_Bundled());
$data = $exif->getData('photo.jpg');

// Modern
$reader = new BundledReader();
$meta = $reader->read('photo.jpg');
$gps = $meta->gps();
```

## Removed Features

| Feature | Reason | Alternative |
|---------|--------|-------------|
| SWF/Flash backend | Flash EOL 2020 | None needed |
| HTTP response helpers | Application layer concern | Use PSR-7 response objects |
| Global `Horde_Image::factory()` | Static pattern from Horde 3/4 era, no longer desirable | Use `ImageFactory` with DI |

## Migration Strategy

1. Add `"Horde\\Image\\": "src/"` to your PSR-4 autoload (already in composer.json)
2. New code uses `Horde\Image\*` classes
3. Existing code continues using `Horde_Image_*` from `lib/`
4. Migrate incrementally per-feature, per-module
5. Once all consumers are migrated, remove `lib/` dependency

---

## Older Upgrades (Horde_Image 1.x/2.x)

### Upgrading to 2.3.0

- **Horde_Image_Effect_Gd_Border** - Replaces and fixes the generic border effect implementation.
- **Horde_Image_Effect_Imagick_LiquidResize** - A `ratio` parameter has been added.
- **Horde_Image_Rgb** - This class holds a map from HTML color names to RGB values and replaces the global `$horde_image_rgb_colors` variable.
