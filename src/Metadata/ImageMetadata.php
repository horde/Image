<?php

declare(strict_types=1);

namespace Horde\Image\Metadata;

use DateTimeImmutable;

final class ImageMetadata
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data = [],
    ) {}

    public function get(MetadataField $field): mixed
    {
        return $this->data[$field->value] ?? null;
    }

    public function has(MetadataField $field): bool
    {
        return isset($this->data[$field->value]);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function gps(): ?GpsCoordinate
    {
        $lat = $this->get(MetadataField::GPSLatitude);
        $lon = $this->get(MetadataField::GPSLongitude);

        if ($lat === null || $lon === null) {
            return null;
        }

        return new GpsCoordinate((float) $lat, (float) $lon);
    }

    public function dateOriginal(): ?DateTimeImmutable
    {
        $ts = $this->get(MetadataField::DateTimeOriginal);
        if ($ts === null) {
            return null;
        }

        if (is_int($ts)) {
            return (new DateTimeImmutable())->setTimestamp($ts);
        }

        $dt = DateTimeImmutable::createFromFormat('Y:m:d H:i:s', (string) $ts);
        return $dt ?: null;
    }

    public function camera(): ?string
    {
        $make = $this->get(MetadataField::Make);
        $model = $this->get(MetadataField::Model);

        if ($make === null && $model === null) {
            return null;
        }

        if ($make !== null && $model !== null) {
            if (str_starts_with((string) $model, (string) $make)) {
                return (string) $model;
            }
            return trim($make . ' ' . $model);
        }

        return (string) ($make ?? $model);
    }

    public function title(): ?string
    {
        foreach (MetadataField::titleFields() as $field) {
            $val = $this->get($field);
            if ($val !== null && $val !== '') {
                return (string) $val;
            }
        }
        return null;
    }

    public function description(): ?string
    {
        foreach (MetadataField::descriptionFields() as $field) {
            $val = $this->get($field);
            if ($val !== null && $val !== '') {
                return (string) $val;
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function merge(array $data): self
    {
        return new self(array_merge($this->data, $data));
    }
}
