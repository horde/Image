<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Parser;

use Horde\Image\Metadata\GpsCoordinate;

final class GpsParser
{
    /**
     * Parse GPS data from EXIF-style array format.
     *
     * @param array<string, mixed> $data Raw EXIF data containing GPS fields
     */
    public static function parse(array $data): ?GpsCoordinate
    {
        $lat = self::parseCoordinate(
            $data['GPSLatitude'] ?? null,
            $data['GPSLatitudeRef'] ?? null,
        );

        $lon = self::parseCoordinate(
            $data['GPSLongitude'] ?? null,
            $data['GPSLongitudeRef'] ?? null,
        );

        if ($lat === null || $lon === null) {
            return null;
        }

        $altitude = self::parseAltitude(
            $data['GPSAltitude'] ?? null,
            $data['GPSAltitudeRef'] ?? null,
        );

        return new GpsCoordinate($lat, $lon, $altitude);
    }

    /**
     * Parse a GPS coordinate (latitude or longitude) to decimal degrees.
     *
     * Accepts:
     * - Array of [degrees, minutes, seconds] (fractions like "dd/1")
     * - Scalar decimal value
     * - String "dd/1, mm/1, ss/1" format
     */
    public static function parseCoordinate(mixed $value, ?string $ref = null): ?float
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_string($value) && str_contains($value, ',')) {
            $parts = array_map('trim', explode(',', $value));
            if (count($parts) === 3) {
                $value = $parts;
            }
        }

        if (is_array($value)) {
            if (count($value) < 3) {
                return null;
            }

            $degrees = self::parseFraction($value[0]);
            $minutes = self::parseFraction($value[1]);
            $seconds = self::parseFraction($value[2]);

            if ($degrees == 0 && $minutes == 0 && $seconds == 0) {
                return null;
            }

            $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        } else {
            $decimal = (float) $value;
            if ($decimal == 0.0) {
                return null;
            }
        }

        $decimal = round($decimal, 6);

        if ($ref !== null && in_array($ref, ['S', 'South', 'W', 'West'], true)) {
            $decimal = -abs($decimal);
        }

        return $decimal;
    }

    private static function parseAltitude(mixed $value, ?string $ref): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $altitude = self::parseFraction($value);

        if ($ref === '1') {
            $altitude = -$altitude;
        }

        return $altitude;
    }

    public static function parseFraction(mixed $value): float
    {
        if (is_string($value) && str_contains($value, '/')) {
            $parts = explode('/', $value, 2);
            if (count($parts) === 2 && (float) $parts[1] !== 0.0) {
                return (float) $parts[0] / (float) $parts[1];
            }
            return (float) $parts[0];
        }
        return (float) $value;
    }

    public static function degToDecimal(float $degrees, float $minutes, float $seconds): float
    {
        return round($degrees + ($minutes / 60) + ($seconds / 3600), 6);
    }
}
