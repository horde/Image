<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Metadata;

use Horde\Image\Metadata\MetadataField;
use Horde\Image\Metadata\MetadataFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetadataFormatter::class)]
final class MetadataFormatterTest extends TestCase
{
    public function testNullReturnsEmpty(): void
    {
        $this->assertSame('', MetadataFormatter::humanReadable(MetadataField::Make, null));
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', MetadataFormatter::humanReadable(MetadataField::Make, ''));
    }

    public function testTextFieldReturnsAsIs(): void
    {
        $this->assertSame('Canon', MetadataFormatter::humanReadable(MetadataField::Make, 'Canon'));
    }

    #[DataProvider('exposureModeProvider')]
    public function testExposureMode(int $value, string $expected): void
    {
        $this->assertSame($expected, MetadataFormatter::humanReadable(MetadataField::ExposureMode, $value));
    }

    public static function exposureModeProvider(): array
    {
        return [
            [0, 'Auto exposure'],
            [1, 'Manual exposure'],
            [2, 'Auto bracket'],
            [99, 'Unknown'],
        ];
    }

    #[DataProvider('exposureProgramProvider')]
    public function testExposureProgram(int $value, string $expected): void
    {
        $this->assertSame($expected, MetadataFormatter::humanReadable(MetadataField::ExposureProgram, $value));
    }

    public static function exposureProgramProvider(): array
    {
        return [
            [1, 'Manual'],
            [2, 'Normal Program'],
            [3, 'Aperture Priority'],
            [4, 'Shutter Priority'],
            [7, 'Portrait'],
            [8, 'Landscape'],
        ];
    }

    public function testOrientationNormal(): void
    {
        $this->assertSame('Normal (0 deg)', MetadataFormatter::humanReadable(MetadataField::Orientation, 1));
    }

    public function testOrientation90CW(): void
    {
        $this->assertSame('90 deg CW', MetadataFormatter::humanReadable(MetadataField::Orientation, 8));
    }

    public function testFocalLengthFraction(): void
    {
        $this->assertSame('50 mm', MetadataFormatter::humanReadable(MetadataField::FocalLength, '50/1'));
    }

    public function testFocalLengthDecimal(): void
    {
        $this->assertSame('85 mm', MetadataFormatter::humanReadable(MetadataField::FocalLength, '85'));
    }

    public function testFocalLength35mm(): void
    {
        $this->assertSame('75 mm', MetadataFormatter::humanReadable(MetadataField::FocalLengthIn35mmFilm, '75'));
    }

    public function testFNumberFraction(): void
    {
        $this->assertSame('f/2.8', MetadataFormatter::humanReadable(MetadataField::FNumber, '28/10'));
    }

    public function testFNumberScalar(): void
    {
        $this->assertSame('f/4', MetadataFormatter::humanReadable(MetadataField::FNumber, '4'));
    }

    public function testExposureTimeFraction(): void
    {
        $result = MetadataFormatter::humanReadable(MetadataField::ExposureTime, '1/250');
        $this->assertSame('1/250 sec', $result);
    }

    public function testExposureTimeLong(): void
    {
        $result = MetadataFormatter::humanReadable(MetadataField::ExposureTime, '2/1');
        $this->assertSame('2 sec', $result);
    }

    public function testExposureBiasZero(): void
    {
        $this->assertSame('0 EV', MetadataFormatter::humanReadable(MetadataField::ExposureBiasValue, '0/1'));
    }

    public function testExposureBiasNonZero(): void
    {
        $this->assertSame('1/3 EV', MetadataFormatter::humanReadable(MetadataField::ExposureBiasValue, '1/3'));
    }

    #[DataProvider('meteringModeProvider')]
    public function testMeteringMode(int $value, string $expected): void
    {
        $this->assertSame($expected, MetadataFormatter::humanReadable(MetadataField::MeteringMode, $value));
    }

    public static function meteringModeProvider(): array
    {
        return [
            [1, 'Average'],
            [2, 'Center Weighted Average'],
            [3, 'Spot'],
            [5, 'Multi-Segment'],
            [6, 'Partial'],
            [255, 'Other'],
        ];
    }

    public function testFlashNoFlash(): void
    {
        $this->assertSame('No Flash', MetadataFormatter::humanReadable(MetadataField::Flash, 0));
    }

    public function testFlashFired(): void
    {
        $this->assertSame('Flash', MetadataFormatter::humanReadable(MetadataField::Flash, 1));
    }

    public function testFlashCompulsory(): void
    {
        $this->assertSame('Compulsory Flash', MetadataFormatter::humanReadable(MetadataField::Flash, 9));
    }

    public function testFlashRedEye(): void
    {
        $this->assertSame('Red Eye', MetadataFormatter::humanReadable(MetadataField::Flash, 65));
    }

    public function testFileSizeBytes(): void
    {
        $this->assertSame('500 B', MetadataFormatter::humanReadable(MetadataField::FileSize, 500));
    }

    public function testFileSizeKilobytes(): void
    {
        $this->assertSame('2.5 kB', MetadataFormatter::humanReadable(MetadataField::FileSize, 2560));
    }

    public function testFileSizeMegabytes(): void
    {
        $result = MetadataFormatter::humanReadable(MetadataField::FileSize, 5242880);
        $this->assertSame('5 MB', $result);
    }

    public function testFileSizeZero(): void
    {
        $this->assertSame('0 B', MetadataFormatter::humanReadable(MetadataField::FileSize, 0));
    }

    public function testColorSpaceSrgb(): void
    {
        $this->assertSame('sRGB', MetadataFormatter::humanReadable(MetadataField::ColorSpace, 1));
    }

    public function testColorSpaceUncalibrated(): void
    {
        $this->assertSame('Uncalibrated', MetadataFormatter::humanReadable(MetadataField::ColorSpace, 65535));
    }

    public function testWhiteBalanceAuto(): void
    {
        $this->assertSame('Auto', MetadataFormatter::humanReadable(MetadataField::WhiteBalance, 0));
    }

    public function testWhiteBalanceManual(): void
    {
        $this->assertSame('Manual', MetadataFormatter::humanReadable(MetadataField::WhiteBalance, 1));
    }

    #[DataProvider('lightSourceProvider')]
    public function testLightSource(int $value, string $expected): void
    {
        $this->assertSame($expected, MetadataFormatter::humanReadable(MetadataField::LightSource, $value));
    }

    public static function lightSourceProvider(): array
    {
        return [
            [0, 'Unknown'],
            [1, 'Daylight'],
            [2, 'Fluorescent'],
            [3, 'Tungsten'],
            [9, 'Fine weather'],
            [255, 'Other'],
        ];
    }

    public function testResolutionUnitInch(): void
    {
        $this->assertSame('Inch', MetadataFormatter::humanReadable(MetadataField::ResolutionUnit, 2));
    }

    public function testResolutionFraction(): void
    {
        $this->assertSame('72 dots per unit', MetadataFormatter::humanReadable(MetadataField::XResolution, '72/1'));
    }

    public function testImageWidthPixels(): void
    {
        $this->assertSame('4000 pixels', MetadataFormatter::humanReadable(MetadataField::ExifImageWidth, 4000));
    }

    #[DataProvider('sensingMethodProvider')]
    public function testSensingMethod(int $value, string $expected): void
    {
        $this->assertSame($expected, MetadataFormatter::humanReadable(MetadataField::SensingMethod, $value));
    }

    public static function sensingMethodProvider(): array
    {
        return [
            [1, 'Not defined'],
            [2, 'One Chip Color Area Sensor'],
            [7, 'Trilinear Sensor'],
        ];
    }

    #[DataProvider('sceneCaptureProvider')]
    public function testSceneCaptureType(int $value, string $expected): void
    {
        $this->assertSame($expected, MetadataFormatter::humanReadable(MetadataField::SceneCaptureType, $value));
    }

    public static function sceneCaptureProvider(): array
    {
        return [
            [0, 'Standard'],
            [1, 'Landscape'],
            [2, 'Portrait'],
            [3, 'Night Scene'],
        ];
    }

    public function testDateFromTimestamp(): void
    {
        $result = MetadataFormatter::humanReadable(MetadataField::DateTime, 1710500000);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    public function testDateFromString(): void
    {
        $result = MetadataFormatter::humanReadable(MetadataField::DateTime, '2024:03:15 10:30:45');
        $this->assertSame('2024:03:15 10:30:45', $result);
    }

    public function testApertureFraction(): void
    {
        $result = MetadataFormatter::humanReadable(MetadataField::ApertureValue, '4/1');
        $this->assertStringStartsWith('f/', $result);
    }
}
