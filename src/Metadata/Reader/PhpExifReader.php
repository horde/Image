<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Reader;

use Horde\Image\DriverException;
use Horde\Image\Metadata\FieldType;
use Horde\Image\Metadata\ImageMetadata;
use Horde\Image\Metadata\MetadataCategory;
use Horde\Image\Metadata\MetadataField;
use Horde\Image\Metadata\MetadataReader;

final class PhpExifReader implements MetadataReader
{
    public function __construct()
    {
        if (!function_exists('exif_read_data')) {
            throw new DriverException('PHP exif extension is required for PhpExifReader');
        }
    }

    public function readFile(string $path): ImageMetadata
    {
        $raw = @exif_read_data($path, '', false);
        if ($raw === false) {
            return new ImageMetadata();
        }

        return new ImageMetadata($this->processData($raw));
    }

    public function readData(string $data): ImageMetadata
    {
        $tmp = tempnam(sys_get_temp_dir(), 'horde_exif_');
        if ($tmp === false) {
            return new ImageMetadata();
        }

        try {
            file_put_contents($tmp, $data);
            return $this->readFile($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    public function supportedCategories(): array
    {
        return [MetadataCategory::Exif];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function processData(array $raw): array
    {
        $results = [];
        $supportedFields = MetadataField::forCategory(MetadataCategory::Exif);

        foreach ($supportedFields as $field) {
            $key = $field->value;
            $value = $raw[$key] ?? '';

            if ($value === '' || $value === []) {
                continue;
            }

            if ($field->type() === FieldType::Gps) {
                $value = $this->parseGps($raw, $key);
                if ($value === null) {
                    continue;
                }
            } elseif ($field->type() === FieldType::Date) {
                $value = $this->parseDate($value);
                if ($value === null) {
                    continue;
                }
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            }

            $results[$key] = $value;
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function parseGps(array $raw, string $key): ?float
    {
        $value = $raw[$key] ?? null;
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return (float) $value;
        }

        if (count($value) < 3 || $value[0] == 0) {
            return null;
        }

        $degrees = $this->parseFraction($value[0]);
        $minutes = $this->parseFraction($value[1]);
        $seconds = $this->parseFraction($value[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        $decimal = round($decimal, 6);

        $ref = $raw[$key . 'Ref'] ?? '';
        if (in_array($ref, ['S', 'South', 'W', 'West'], true)) {
            $decimal = -abs($decimal);
        }

        return $decimal;
    }

    private function parseFraction(mixed $value): float
    {
        if (is_string($value) && str_contains($value, '/')) {
            $parts = explode('/', $value, 2);
            if (count($parts) === 2 && (float) $parts[1] !== 0.0) {
                return (float) $parts[0] / (float) $parts[1];
            }
        }
        return (float) $value;
    }

    private function parseDate(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        $value = (string) $value;
        $parts = explode(' ', $value, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$ymd, $hms] = $parts;
        [$year, $month, $day] = explode(':', $ymd, 3);

        if (empty($year) || empty($month) || empty($day)) {
            return null;
        }

        $ts = strtotime("$month/$day/$year $hms");
        return $ts !== false ? $ts : null;
    }
}
