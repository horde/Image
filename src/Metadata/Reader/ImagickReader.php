<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Reader;

use Horde\Image\DriverException;
use Horde\Image\Metadata\FieldType;
use Horde\Image\Metadata\ImageMetadata;
use Horde\Image\Metadata\MetadataCategory;
use Horde\Image\Metadata\MetadataField;
use Horde\Image\Metadata\MetadataReader;
use Imagick;
use ImagickException;

final class ImagickReader implements MetadataReader
{
    public function __construct()
    {
        if (!extension_loaded('imagick')) {
            throw new DriverException('ext-imagick is required for ImagickReader');
        }
    }

    public function readFile(string $path): ImageMetadata
    {
        $imagick = new Imagick();

        try {
            $imagick->readImage($path);
        } catch (ImagickException $e) {
            return new ImageMetadata();
        }

        return $this->extract($imagick);
    }

    public function readData(string $data): ImageMetadata
    {
        $imagick = new Imagick();

        try {
            $imagick->readImageBlob($data);
        } catch (ImagickException $e) {
            return new ImageMetadata();
        }

        return $this->extract($imagick);
    }

    public function supportedCategories(): array
    {
        return [MetadataCategory::Exif, MetadataCategory::Iptc, MetadataCategory::Xmp];
    }

    private function extract(Imagick $imagick): ImageMetadata
    {
        $props = $imagick->getImageProperties();
        $results = [];

        foreach (MetadataField::cases() as $field) {
            $category = $field->category();
            if (!in_array($category, $this->supportedCategories(), true)) {
                continue;
            }

            $value = $this->findProperty($props, $field);
            if ($value === null || $value === '') {
                continue;
            }

            if ($field->type() === FieldType::Gps) {
                $value = $this->parseGpsProperty($props, $field);
                if ($value === null) {
                    continue;
                }
            } elseif ($field->type() === FieldType::Date) {
                $value = $this->parseDate($value);
                if ($value === null) {
                    continue;
                }
            }

            $results[$field->value] = $value;
        }

        $imagick->clear();
        return new ImageMetadata($results);
    }

    /**
     * @param array<string, string> $props
     */
    private function findProperty(array $props, MetadataField $field): ?string
    {
        $key = $field->value;

        $prefixes = match ($field->category()) {
            MetadataCategory::Exif => ['exif:', 'exif:thumbnail:', ''],
            MetadataCategory::Iptc => ['iptc:', ''],
            MetadataCategory::Xmp => ['xmp:', 'xmp-dc:', ''],
            MetadataCategory::Composite => [''],
        };

        foreach ($prefixes as $prefix) {
            $fullKey = $prefix . $key;
            if (isset($props[$fullKey])) {
                return $props[$fullKey];
            }
        }

        $keyLower = strtolower($key);
        foreach ($props as $propKey => $propValue) {
            if (strtolower(basename(str_replace(':', '/', $propKey))) === $keyLower) {
                return $propValue;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $props
     */
    private function parseGpsProperty(array $props, MetadataField $field): ?float
    {
        $value = $this->findProperty($props, $field);
        if ($value === null) {
            return null;
        }

        $decimal = $this->parseDmsString($value);
        if ($decimal === null) {
            $decimal = (float) $value;
        }

        $refKey = $field->value . 'Ref';
        $ref = $props['exif:' . $refKey] ?? $props[$refKey] ?? null;
        if ($ref !== null && in_array($ref, ['S', 'South', 'W', 'West'], true)) {
            $decimal = -abs($decimal);
        }

        return round($decimal, 6);
    }

    private function parseDmsString(string $value): ?float
    {
        if (preg_match('/^(\d+)[\/,]\s*(\d+)\s*(\d+)[\/,]\s*(\d+)\s*(\d+)[\/,]\s*(\d+)$/', $value, $m)) {
            $deg = (int) $m[1] / max((int) $m[2], 1);
            $min = (int) $m[3] / max((int) $m[4], 1);
            $sec = (int) $m[5] / max((int) $m[6], 1);
            return $deg + ($min / 60) + ($sec / 3600);
        }

        if (str_contains($value, ',')) {
            $parts = explode(',', $value);
            if (count($parts) === 3) {
                $deg = $this->parseFraction(trim($parts[0]));
                $min = $this->parseFraction(trim($parts[1]));
                $sec = $this->parseFraction(trim($parts[2]));
                return $deg + ($min / 60) + ($sec / 3600);
            }
        }

        return null;
    }

    private function parseFraction(string $value): float
    {
        if (str_contains($value, '/')) {
            $parts = explode('/', $value, 2);
            if (count($parts) === 2 && (float) $parts[1] !== 0.0) {
                return (float) $parts[0] / (float) $parts[1];
            }
        }
        return (float) $value;
    }

    private function parseDate(string $value): ?int
    {
        $parts = explode(' ', $value, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$ymd, $hms] = $parts;
        $dateParts = explode(':', $ymd, 3);
        if (count($dateParts) !== 3) {
            return null;
        }

        [$year, $month, $day] = $dateParts;
        if (empty($year) || empty($month) || empty($day)) {
            return null;
        }

        $ts = strtotime("$month/$day/$year $hms");
        return $ts !== false ? $ts : null;
    }
}
