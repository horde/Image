<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Reader;

use Horde\Image\Metadata\FieldType;
use Horde\Image\Metadata\ImageMetadata;
use Horde\Image\Metadata\MetadataCategory;
use Horde\Image\Metadata\MetadataField;
use Horde\Image\Metadata\MetadataReader;
use Horde\Image\Metadata\Parser\GpsParser;
use Horde\Image\Metadata\Parser\MakerNoteParser;

/**
 * Bundled EXIF reader — parses JPEG binary structure directly.
 *
 * Based on Jake Olefsky's Exifer library (GPL), heavily refactored.
 */
final class BundledReader implements MetadataReader
{
    /** @var list<MakerNoteParser> */
    private readonly array $makerNoteParsers;

    /**
     * @param list<MakerNoteParser> $makerNoteParsers
     */
    public function __construct(array $makerNoteParsers = [])
    {
        $this->makerNoteParsers = $makerNoteParsers;
    }

    public function readFile(string $path): ImageMetadata
    {
        $stream = @fopen($path, 'rb');
        if ($stream === false) {
            return new ImageMetadata();
        }

        try {
            $raw = $this->parseJpeg($stream);
            $raw['FileSize'] = filesize($path) ?: 0;
            return new ImageMetadata($this->processRaw($raw));
        } finally {
            fclose($stream);
        }
    }

    public function readData(string $data): ImageMetadata
    {
        $stream = fopen('php://memory', 'r+b');
        if ($stream === false) {
            return new ImageMetadata();
        }

        fwrite($stream, $data);
        rewind($stream);

        try {
            $raw = $this->parseJpeg($stream);
            $raw['FileSize'] = strlen($data);
            return new ImageMetadata($this->processRaw($raw));
        } finally {
            fclose($stream);
        }
    }

    public function supportedCategories(): array
    {
        return [MetadataCategory::Exif];
    }

    /**
     * @param resource $stream
     * @return array<string, mixed>
     */
    private function parseJpeg($stream): array
    {
        $header = fread($stream, 2);
        if ($header === false || bin2hex($header) !== 'ffd8') {
            return [];
        }

        while (!feof($stream)) {
            $marker = fread($stream, 2);
            if ($marker === false || strlen($marker) < 2) {
                break;
            }

            $markerHex = bin2hex($marker);

            if ($markerHex === 'ffd9' || $markerHex === 'ffc0') {
                break;
            }

            $sizeBytes = fread($stream, 2);
            if ($sizeBytes === false || strlen($sizeBytes) < 2) {
                break;
            }
            $unpacked = unpack('n', $sizeBytes);
            $size = $unpacked !== false ? $unpacked[1] : 0;

            if ($markerHex === 'ffe1') {
                $readLen = $size - 2;
                if ($readLen < 1) {
                    return [];
                }
                $exifData = fread($stream, $readLen);
                if ($exifData !== false && str_starts_with($exifData, "Exif\x00\x00")) {
                    return $this->parseTiff(substr($exifData, 6));
                }
                return [];
            }

            fseek($stream, $size - 2, SEEK_CUR);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseTiff(string $data): array
    {
        if (strlen($data) < 8) {
            return [];
        }

        $byteOrder = substr($data, 0, 2);
        $intel = ($byteOrder === "II");

        $magic = $this->readShort($data, 2, $intel);
        if ($magic !== 0x002A) {
            return [];
        }

        $ifd0Offset = $this->readLong($data, 4, $intel);
        $result = [];

        $ifd0 = $this->readIfd($data, $ifd0Offset, $intel);
        $result = array_merge($result, $ifd0);

        if (isset($ifd0['ExifIFDPointer'])) {
            $subIfd = $this->readIfd($data, (int) $ifd0['ExifIFDPointer'], $intel);
            unset($result['ExifIFDPointer']);
            $result = array_merge($result, $subIfd);

            if (isset($subIfd['MakerNote']) && isset($result['Make'])) {
                $makerData = $this->parseMakerNote((string) $result['Make'], $subIfd['MakerNote'], $intel);
                $result = array_merge($result, $makerData);
                unset($result['MakerNote']);
            }
        }

        if (isset($ifd0['GPSInfoIFDPointer'])) {
            $gpsIfd = $this->readIfd($data, (int) $ifd0['GPSInfoIFDPointer'], $intel);
            unset($result['GPSInfoIFDPointer']);
            $this->processGpsIfd($gpsIfd, $result);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function readIfd(string $data, int $offset, bool $intel): array
    {
        if ($offset + 2 > strlen($data)) {
            return [];
        }

        $count = $this->readShort($data, $offset, $intel);
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $entryOffset = $offset + 2 + ($i * 12);
            if ($entryOffset + 12 > strlen($data)) {
                break;
            }

            $tag = $this->readShort($data, $entryOffset, $intel);
            $type = $this->readShort($data, $entryOffset + 2, $intel);
            $numValues = $this->readLong($data, $entryOffset + 4, $intel);
            $valueOffset = $entryOffset + 8;

            $byteCount = $this->typeSize($type) * $numValues;
            if ($byteCount > 4) {
                $valueOffset = $this->readLong($data, $entryOffset + 8, $intel);
            }

            if ($valueOffset + $byteCount > strlen($data)) {
                continue;
            }

            $name = $this->tagName($tag);
            $value = $this->readValue($data, $valueOffset, $type, $numValues, $intel);

            if ($name !== null) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    private function readValue(string $data, int $offset, int $type, int $count, bool $intel): mixed
    {
        return match ($type) {
            1 => ord($data[$offset]),                    // UBYTE
            2 => rtrim(substr($data, $offset, $count), "\x00"), // ASCII
            3 => $count === 1
                ? $this->readShort($data, $offset, $intel)
                : $this->readShortArray($data, $offset, $count, $intel),
            4 => $this->readLong($data, $offset, $intel),    // ULONG
            5 => $count === 1
                ? $this->readRational($data, $offset, $intel)
                : $this->readRationalArray($data, $offset, $count, $intel), // URATIONAL
            6 => $this->readSignedByte($data, $offset),      // SBYTE
            7 => substr($data, $offset, $count),             // UNDEFINED
            8 => $this->readSignedShort($data, $offset, $intel), // SSHORT
            9 => $this->readSignedLong($data, $offset, $intel),  // SLONG
            10 => $this->readSignedRational($data, $offset, $intel), // SRATIONAL
            default => null,
        };
    }

    private function readShort(string $data, int $offset, bool $intel): int
    {
        $bytes = substr($data, $offset, 2);
        if (strlen($bytes) < 2) {
            return 0;
        }
        $unpacked = $intel ? unpack('v', $bytes) : unpack('n', $bytes);
        return $unpacked !== false ? $unpacked[1] : 0;
    }

    /**
     * @return list<int>
     */
    private function readShortArray(string $data, int $offset, int $count, bool $intel): array
    {
        $values = [];
        for ($i = 0; $i < $count; $i++) {
            $values[] = $this->readShort($data, $offset + ($i * 2), $intel);
        }
        return $values;
    }

    /**
     * @return list<string>
     */
    private function readRationalArray(string $data, int $offset, int $count, bool $intel): array
    {
        $values = [];
        for ($i = 0; $i < $count; $i++) {
            $values[] = $this->readRational($data, $offset + ($i * 8), $intel);
        }
        return $values;
    }

    private function readLong(string $data, int $offset, bool $intel): int
    {
        $bytes = substr($data, $offset, 4);
        if (strlen($bytes) < 4) {
            return 0;
        }
        $unpacked = $intel ? unpack('V', $bytes) : unpack('N', $bytes);
        return $unpacked !== false ? $unpacked[1] : 0;
    }

    private function readRational(string $data, int $offset, bool $intel): string
    {
        $num = $this->readLong($data, $offset, $intel);
        $den = $this->readLong($data, $offset + 4, $intel);
        if ($den === 0) {
            return '0';
        }
        return $num . '/' . $den;
    }

    private function readSignedByte(string $data, int $offset): int
    {
        $val = ord($data[$offset]);
        return $val > 127 ? $val - 256 : $val;
    }

    private function readSignedShort(string $data, int $offset, bool $intel): int
    {
        $val = $this->readShort($data, $offset, $intel);
        return $val > 32767 ? $val - 65536 : $val;
    }

    private function readSignedLong(string $data, int $offset, bool $intel): int
    {
        $val = $this->readLong($data, $offset, $intel);
        return $val > 2147483647 ? $val - 4294967296 : $val;
    }

    private function readSignedRational(string $data, int $offset, bool $intel): string
    {
        $num = $this->readSignedLong($data, $offset, $intel);
        $den = $this->readSignedLong($data, $offset + 4, $intel);
        if ($den === 0) {
            return '0';
        }
        return $num . '/' . $den;
    }

    private function typeSize(int $type): int
    {
        return match ($type) {
            1, 2, 6, 7 => 1,
            3, 8 => 2,
            4, 9, 11 => 4,
            5, 10, 12 => 8,
            default => 1,
        };
    }

    private function tagName(int $tag): ?string
    {
        return match ($tag) {
            0x010F => 'Make',
            0x0110 => 'Model',
            0x0112 => 'Orientation',
            0x011A => 'XResolution',
            0x011B => 'YResolution',
            0x0128 => 'ResolutionUnit',
            0x0131 => 'Software',
            0x0132 => 'DateTime',
            0x013B => 'Artist',
            0x8298 => 'Copyright',
            0x8769 => 'ExifIFDPointer',
            0x8825 => 'GPSInfoIFDPointer',

            // SubIFD tags
            0x829A => 'ExposureTime',
            0x829D => 'FNumber',
            0x8822 => 'ExposureProgram',
            0x8827 => 'ISOSpeedRatings',
            0x9000 => 'ExifVersion',
            0x9003 => 'DateTimeOriginal',
            0x9004 => 'DateTimeDigitized',
            0x9201 => 'ShutterSpeedValue',
            0x9202 => 'ApertureValue',
            0x9204 => 'ExposureBiasValue',
            0x9207 => 'MeteringMode',
            0x9208 => 'LightSource',
            0x9209 => 'Flash',
            0x920A => 'FocalLength',
            0x9286 => 'UserComment',
            0xA001 => 'ColorSpace',
            0xA002 => 'ExifImageWidth',
            0xA003 => 'ExifImageLength',
            0xA210 => 'FocalPlaneResolutionUnit',
            0xA217 => 'SensingMethod',
            0xA401 => 'CustomRendered',
            0xA402 => 'ExposureMode',
            0xA403 => 'WhiteBalance',
            0xA405 => 'FocalLengthIn35mmFilm',
            0xA406 => 'SceneCaptureType',
            0x927C => 'MakerNote',
            0xA434 => 'Lens',

            // GPS tags
            0x0000 => 'GPSVersionID',
            0x0001 => 'GPSLatitudeRef',
            0x0002 => 'GPSLatitude',
            0x0003 => 'GPSLongitudeRef',
            0x0004 => 'GPSLongitude',
            0x0005 => 'GPSAltitudeRef',
            0x0006 => 'GPSAltitude',

            default => null,
        };
    }

    /**
     * @param array<string, mixed> $gpsIfd
     * @param array<string, mixed> $result
     */
    private function processGpsIfd(array $gpsIfd, array &$result): void
    {
        if (isset($gpsIfd['GPSLatitude']) && isset($gpsIfd['GPSLongitude'])) {
            $latRef = $gpsIfd['GPSLatitudeRef'] ?? 'N';
            $lonRef = $gpsIfd['GPSLongitudeRef'] ?? 'E';

            $lat = $this->parseGpsCoord($gpsIfd['GPSLatitude']);
            $lon = $this->parseGpsCoord($gpsIfd['GPSLongitude']);

            if ($lat !== null) {
                if (in_array($latRef, ['S', 'South'], true)) {
                    $lat = -abs($lat);
                }
                $result['GPSLatitude'] = $lat;
            }

            if ($lon !== null) {
                if (in_array($lonRef, ['W', 'West'], true)) {
                    $lon = -abs($lon);
                }
                $result['GPSLongitude'] = $lon;
            }
        }
    }

    private function parseGpsCoord(mixed $value): ?float
    {
        if (is_array($value) && count($value) >= 3) {
            $deg = GpsParser::parseFraction($value[0]);
            $min = GpsParser::parseFraction($value[1]);
            $sec = GpsParser::parseFraction($value[2]);
            return GpsParser::degToDecimal($deg, $min, $sec);
        }

        if (is_string($value)) {
            return GpsParser::parseCoordinate($value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseMakerNote(string $make, mixed $noteData, bool $intel): array
    {
        if (!is_string($noteData)) {
            return [];
        }

        $makeLower = strtolower(trim($make));
        foreach ($this->makerNoteParsers as $parser) {
            if ($parser->supports($makeLower)) {
                return $parser->parse($noteData, $intel);
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function processRaw(array $raw): array
    {
        $results = [];
        $supportedFields = MetadataField::forCategory(MetadataCategory::Exif);

        foreach ($supportedFields as $field) {
            $key = $field->value;
            $value = $raw[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if ($field->type() === FieldType::Gps) {
                if (!is_numeric($value)) {
                    continue;
                }
                $results[$key] = (float) $value;
                continue;
            }

            if ($field->type() === FieldType::Date) {
                if (is_string($value)) {
                    $parts = explode(' ', $value, 2);
                    if (count($parts) === 2) {
                        [$ymd, $hms] = $parts;
                        $dateParts = explode(':', $ymd, 3);
                        if (count($dateParts) === 3) {
                            [$year, $month, $day] = $dateParts;
                            $ts = strtotime("$month/$day/$year $hms");
                            if ($ts !== false) {
                                $value = $ts;
                            }
                        }
                    }
                }
            }

            if (is_array($value)) {
                $value = implode(',', array_map('strval', $value));
            }

            $results[$key] = $value;
        }

        if (isset($raw['FileSize'])) {
            $results['FileSize'] = (int) $raw['FileSize'];
        }

        return $results;
    }
}
