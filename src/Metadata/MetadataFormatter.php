<?php

declare(strict_types=1);

namespace Horde\Image\Metadata;

final class MetadataFormatter
{
    public static function humanReadable(MetadataField $field, mixed $data): string
    {
        if ($data === null || $data === '') {
            return '';
        }

        return match ($field) {
            MetadataField::ExposureMode => self::formatExposureMode($data),
            MetadataField::ExposureProgram => self::formatExposureProgram($data),
            MetadataField::XResolution,
            MetadataField::YResolution => self::formatResolution($data),
            MetadataField::ResolutionUnit => self::formatResolutionUnit($data),
            MetadataField::ExifImageWidth,
            MetadataField::ExifImageLength => $data . ' pixels',
            MetadataField::Orientation => self::formatOrientation($data),
            MetadataField::ExposureTime => self::formatExposureTime($data),
            MetadataField::ShutterSpeedValue => self::formatShutterSpeed($data),
            MetadataField::ApertureValue => self::formatAperture($data),
            MetadataField::FocalLength => self::formatFocalLength($data),
            MetadataField::FocalLengthIn35mmFilm => $data . ' mm',
            MetadataField::FNumber => self::formatFNumber($data),
            MetadataField::ExposureBiasValue => self::formatExposureBias($data),
            MetadataField::MeteringMode => self::formatMeteringMode($data),
            MetadataField::LightSource => self::formatLightSource($data),
            MetadataField::WhiteBalance => self::formatWhiteBalance($data),
            MetadataField::Flash => self::formatFlash($data),
            MetadataField::FileSize => self::formatFileSize($data),
            MetadataField::SensingMethod => self::formatSensingMethod($data),
            MetadataField::ColorSpace => self::formatColorSpace($data),
            MetadataField::SceneCaptureType => self::formatSceneCaptureType($data),
            MetadataField::DateTime,
            MetadataField::DateTimeOriginal,
            MetadataField::DateTimeDigitized => self::formatDate($data),
            default => (string) $data,
        };
    }

    private static function formatExposureMode(mixed $data): string
    {
        return match ((int) $data) {
            0 => 'Auto exposure',
            1 => 'Manual exposure',
            2 => 'Auto bracket',
            default => 'Unknown',
        };
    }

    private static function formatExposureProgram(mixed $data): string
    {
        return match ((int) $data) {
            1 => 'Manual',
            2 => 'Normal Program',
            3 => 'Aperture Priority',
            4 => 'Shutter Priority',
            5 => 'Creative',
            6 => 'Action',
            7 => 'Portrait',
            8 => 'Landscape',
            default => 'Unknown',
        };
    }

    private static function formatResolution(mixed $data): string
    {
        if (is_string($data) && str_contains($data, '/')) {
            [$n, $d] = explode('/', $data, 2);
            return (int) $n . ' dots per unit';
        }
        return $data . ' per unit';
    }

    private static function formatResolutionUnit(mixed $data): string
    {
        return match ((int) $data) {
            1 => 'Pixels',
            2 => 'Inch',
            3 => 'Centimeter',
            default => 'Unknown',
        };
    }

    private static function formatOrientation(mixed $data): string
    {
        return match ((int) $data) {
            1 => 'Normal (0 deg)',
            2 => 'Mirrored',
            3 => 'Upside down',
            4 => 'Upside down Mirrored',
            5 => '90 deg CW Mirrored',
            6 => '90 deg CCW',
            7 => '90 deg CCW Mirrored',
            8 => '90 deg CW',
            default => 'Unknown',
        };
    }

    private static function formatExposureTime(mixed $data): string
    {
        if (is_string($data) && str_contains($data, '/')) {
            [$n, $d] = explode('/', $data, 2);
            if ((int) $d === 0) {
                return 'Unknown';
            }
            $data = (float) $n / (float) $d;
        }
        return self::formatExposure((float) $data);
    }

    private static function formatShutterSpeed(mixed $data): string
    {
        if (is_string($data) && str_contains($data, '/')) {
            [$n, $d] = explode('/', $data, 2);
            if ((int) $d === 0) {
                return 'Unknown';
            }
            $data = (float) $n / (float) $d;
        }
        $data = exp((float) $data * log(2));
        if ($data > 0) {
            $data = 1.0 / $data;
        }
        return self::formatExposure($data);
    }

    private static function formatExposure(float $data): string
    {
        if ($data > 0) {
            if ($data > 1) {
                return round($data, 2) . ' sec';
            }
            $n = 0;
            $d = 0;
            self::convertToFraction($data, $n, $d);
            if ($n !== 1) {
                return sprintf('%.4f sec', $n / $d);
            }
            return $n . '/' . $d . ' sec';
        }
        return 'Bulb';
    }

    private static function convertToFraction(float $v, int &$n, int &$d): void
    {
        $maxTerms = 15;
        $minDivisor = 0.000001;
        $maxError = 0.00000001;

        $f = $v;
        $nUn = 1;
        $dUn = 0;
        $nDeux = 0;
        $dDeux = 1;

        for ($i = 0; $i < $maxTerms; $i++) {
            $a = (int) floor($f);
            $f = $f - $a;
            $n = $nUn * $a + $nDeux;
            $d = $dUn * $a + $dDeux;
            $nDeux = $nUn;
            $dDeux = $dUn;
            $nUn = $n;
            $dUn = $d;

            if ($f < $minDivisor) {
                break;
            }
            if (abs($v - $n / $d) < $maxError) {
                break;
            }
            $f = 1.0 / $f;
        }
    }

    private static function formatAperture(mixed $data): string
    {
        if (is_string($data) && str_contains($data, '/')) {
            [$n, $d] = explode('/', $data, 2);
            if ((int) $d === 0) {
                return 'Unknown';
            }
            $data = (float) $n / (float) $d;
            $data = exp(($data * log(2)) / 2);
            $data = round($data, 1);
        }
        return 'f/' . $data;
    }

    private static function formatFocalLength(mixed $data): string
    {
        if (is_string($data) && str_contains($data, '/')) {
            [$n, $d] = explode('/', $data, 2);
            if ((int) $d === 0) {
                return 'Unknown';
            }
            return round((float) $n / (float) $d) . ' mm';
        }
        return $data . ' mm';
    }

    private static function formatFNumber(mixed $data): string
    {
        if (is_string($data) && str_contains($data, '/')) {
            [$n, $d] = explode('/', $data, 2);
            if ((int) $d !== 0) {
                return 'f/' . round((float) $n / (float) $d, 1);
            }
        }
        return 'f/' . $data;
    }

    private static function formatExposureBias(mixed $data): string
    {
        if (is_string($data) && str_contains($data, '/')) {
            [$n] = explode('/', $data, 2);
            if ((int) $n === 0) {
                return '0 EV';
            }
        }
        return $data . ' EV';
    }

    private static function formatMeteringMode(mixed $data): string
    {
        return match ((int) $data) {
            0 => 'Unknown',
            1 => 'Average',
            2 => 'Center Weighted Average',
            3 => 'Spot',
            4 => 'Multi-Spot',
            5 => 'Multi-Segment',
            6 => 'Partial',
            255 => 'Other',
            default => 'Unknown: ' . $data,
        };
    }

    private static function formatLightSource(mixed $data): string
    {
        return match ((int) $data) {
            0 => 'Unknown',
            1 => 'Daylight',
            2 => 'Fluorescent',
            3 => 'Tungsten',
            4 => 'Flash',
            9 => 'Fine weather',
            10 => 'Cloudy weather',
            11 => 'Shade',
            12 => 'Daylight fluorescent',
            13 => 'Day white fluorescent',
            14 => 'Cool white fluorescent',
            15 => 'White fluorescent',
            17 => 'Standard light A',
            18 => 'Standard light B',
            19 => 'Standard light C',
            20 => 'D55',
            21 => 'D65',
            22 => 'D75',
            23 => 'D50',
            24 => 'ISO studio tungsten',
            255 => 'Other',
            default => 'Unknown',
        };
    }

    private static function formatWhiteBalance(mixed $data): string
    {
        return match ((int) $data) {
            0 => 'Auto',
            1 => 'Manual',
            default => 'Unknown',
        };
    }

    private static function formatFlash(mixed $data): string
    {
        return match ((int) $data) {
            0, 16, 24, 32 => 'No Flash',
            1 => 'Flash',
            5 => 'Flash, strobe return light not detected',
            7 => 'Flash, strobe return light detected',
            9 => 'Compulsory Flash',
            13 => 'Compulsory Flash, Return light not detected',
            15 => 'Compulsory Flash, Return light detected',
            25 => 'Flash, Auto-Mode',
            29 => 'Flash, Auto-Mode, Return light not detected',
            31 => 'Flash, Auto-Mode, Return light detected',
            65 => 'Red Eye',
            69 => 'Red Eye, Return light not detected',
            71 => 'Red Eye, Return light detected',
            73 => 'Red Eye, Compulsory Flash',
            77 => 'Red Eye, Compulsory Flash, Return light not detected',
            79 => 'Red Eye, Compulsory Flash, Return light detected',
            89 => 'Red Eye, Auto-Mode',
            93 => 'Red Eye, Auto-Mode, Return light not detected',
            95 => 'Red Eye, Auto-Mode, Return light detected',
            default => 'Unknown',
        };
    }

    private static function formatFileSize(mixed $data): string
    {
        $data = (int) $data;
        if ($data <= 0) {
            return '0 B';
        }
        $units = ['B', 'kB', 'MB', 'GB'];
        $exp = (int) floor(log($data, 1024));
        $exp = min($exp, count($units) - 1);
        return round($data / pow(1024, $exp), 2) . ' ' . $units[$exp];
    }

    private static function formatSensingMethod(mixed $data): string
    {
        return match ((int) $data) {
            1 => 'Not defined',
            2 => 'One Chip Color Area Sensor',
            3 => 'Two Chip Color Area Sensor',
            4 => 'Three Chip Color Area Sensor',
            5 => 'Color Sequential Area Sensor',
            7 => 'Trilinear Sensor',
            8 => 'Color Sequential Linear Sensor',
            default => 'Unknown',
        };
    }

    private static function formatColorSpace(mixed $data): string
    {
        return match ((int) $data) {
            1 => 'sRGB',
            default => 'Uncalibrated',
        };
    }

    private static function formatSceneCaptureType(mixed $data): string
    {
        return match ((int) $data) {
            0 => 'Standard',
            1 => 'Landscape',
            2 => 'Portrait',
            3 => 'Night Scene',
            default => 'Unknown',
        };
    }

    private static function formatDate(mixed $data): string
    {
        if (is_int($data)) {
            return date('Y-m-d H:i:s', $data);
        }
        return (string) $data;
    }
}
