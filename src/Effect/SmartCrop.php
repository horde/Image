<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Driver\ImageResource;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Horde\Image\Geometry\Size;
use Imagick;

final class SmartCrop implements Effect
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {}

    public function apply(ImageResource $image): ImageResource
    {
        if (!$image instanceof ImagickResource) {
            throw new DriverException('SmartCrop effect requires ImagickResource');
        }

        $imagick = clone $image->imagick();

        $imgWidth = $imagick->getImageWidth();
        $imgHeight = $imagick->getImageHeight();

        if ($imgWidth <= $this->width && $imgHeight <= $this->height) {
            return $image->withImagick($imagick);
        }

        $edge = clone $imagick;
        $edge->edgeImage(1);
        $edge->modulateImage(100, 0, 100);

        $bestX = 0;
        $bestY = 0;
        $bestScore = 0.0;

        $scaleFactors = [1.0, 0.75, 0.5];
        foreach ($scaleFactors as $scale) {
            $testW = (int) round($this->width * $scale);
            $testH = (int) round($this->height * $scale);

            if ($testW > $imgWidth || $testH > $imgHeight) {
                continue;
            }

            for ($xStep = 0; $xStep <= 2; $xStep++) {
                for ($yStep = 0; $yStep <= 2; $yStep++) {
                    $x = (int) round(($imgWidth - $testW) * $xStep / 2);
                    $y = (int) round(($imgHeight - $testH) * $yStep / 2);

                    $region = clone $edge;
                    $region->cropImage($testW, $testH, $x, $y);

                    /** @var array<int, array{mean: float}> $stats */
                    $stats = $region->getImageChannelStatistics();
                    $score = ($stats[1]['mean'] ?? 0.0)
                           + ($stats[2]['mean'] ?? 0.0)
                           + ($stats[4]['mean'] ?? 0.0);
                    $region->destroy();

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestX = $x;
                        $bestY = $y;
                    }
                }
            }
        }

        $edge->destroy();

        $imagick->cropImage($this->width, $this->height, $bestX, $bestY);
        $imagick->setImagePage(0, 0, 0, 0);

        return $image->withImagick($imagick);
    }
}
