# Horde\Image

Image manipulation library for PHP 8.1+ with multiple backend support.

## Features

- **Multiple backends** - ImagickDriver, GdDriver, ImDriver (CLI), SvgDriver, PngDriver (pure PHP), NullDriver
- **Interface Segregation** - ImageDriver for single images, SequenceDriver for animation/multi-frame
- **Immutable resources** - all operations return new ImageResource instances
- **Drawing API** - path-based DrawingContext with state stack, SVG output and brush shapes
- **Filters** - Brightness, Contrast, Gamma, Grayscale, Colorize, Modulate, Sepia, Negate, Sharpen, Pixelate, Blur
- **Effects** - TextWatermark, PolaroidImage, PhotoStack, Border, DropShadow, RoundCorners, SmartCrop, CenterCrop, LiquidResize, Composite
- **Metadata** - EXIF/IPTC/XMP reading with GPS parsing and MakerNote support
- **Sequences** - multi-frame image support (animated GIF, APNG) via ImagickDriver
- **Color utilities** - named colors (140+ CSS names), lighten/darken/intensify and brightness calculation
- **Named font sizes** - FontSize enum with bidirectional point-value lookup
- **DI-friendly** - ImageFactory wrapper, all drivers are constructor-injectable

## Requirements

- PHP 8.1+
- At least one of: ext-imagick, ext-gd or ImageMagick CLI tools. A Pure PHP driver exists for a subset of features.

## Installation

```bash
composer require horde/image
```

## Quick Start

```php
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Color\Color;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Geometry\Size;

$driver = new ImagickDriver();
$image = $driver->create(new Size(800.0, 600.0), Color::named('white'));

$resized = $image->resize(new Size(400.0, 300.0));
$data = $driver->encode($resized, ImageFormat::JPEG);
file_put_contents('output.jpg', $data);
```

See [doc/USAGE.md](doc/USAGE.md) for full documentation.
See [doc/UPGRADING.md](doc/UPRGADING.md) for upgrading calling code from legacy lib/ format or older versions.
See [doc/COPYING-EXIF.md](doc/COPYING-EXIF.md) for copyright and licensing clarification on exifer code included.

## License

LGPL-2.1-only. See [LICENSE](LICENSE) for details.
