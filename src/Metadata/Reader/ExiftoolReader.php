<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Reader;

use Horde\Image\DriverException;
use Horde\Image\Metadata\FieldType;
use Horde\Image\Metadata\ImageMetadata;
use Horde\Image\Metadata\MetadataCategory;
use Horde\Image\Metadata\MetadataField;
use Horde\Image\Metadata\MetadataReader;

final class ExiftoolReader implements MetadataReader
{
    public function __construct(
        private readonly string $exiftoolPath,
    ) {
        if (!is_executable($this->exiftoolPath)) {
            throw new DriverException('Exiftool binary not found or not executable: ' . $this->exiftoolPath);
        }
    }

    public function readFile(string $path): ImageMetadata
    {
        $tags = $this->buildTagArgs();
        $command = sprintf(
            '%s -j %s %s',
            escapeshellarg($this->exiftoolPath),
            $tags,
            escapeshellarg($path),
        );

        $output = [];
        $retval = 0;
        exec($command, $output, $retval);

        $json = implode('', $output);
        $results = json_decode($json, true);

        if (!is_array($results) || $results === []) {
            return new ImageMetadata();
        }

        $raw = (array) array_pop($results);
        return new ImageMetadata($this->processData($raw));
    }

    public function readData(string $data): ImageMetadata
    {
        $tmp = tempnam(sys_get_temp_dir(), 'horde_exiftool_');
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
        return [
            MetadataCategory::Exif,
            MetadataCategory::Iptc,
            MetadataCategory::Xmp,
            MetadataCategory::Composite,
        ];
    }

    private function buildTagArgs(): string
    {
        $tags = [];
        foreach (MetadataField::cases() as $field) {
            $suffix = $field->category() === MetadataCategory::Composite ? '' : '#';
            $tags[] = '-' . $field->value . $suffix;
        }
        return implode(' ', $tags);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function processData(array $raw): array
    {
        $results = [];

        foreach (MetadataField::cases() as $field) {
            $key = $field->value;
            $value = $raw[$key] ?? '';

            if ($value === '') {
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
            } elseif ($field->type() === FieldType::Array_) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
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
        if ($value === null || $value === '') {
            return null;
        }

        $decimal = (float) $value;

        $ref = $raw[$key . 'Ref'] ?? '';
        if (in_array($ref, ['S', 'South', 'W', 'West'], true)) {
            $decimal = -abs($decimal);
        }

        return round($decimal, 6);
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
