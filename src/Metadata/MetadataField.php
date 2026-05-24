<?php

declare(strict_types=1);

namespace Horde\Image\Metadata;

enum MetadataField: string
{
    // IPTC fields
    case Keywords = 'Keywords';
    case ObjectName = 'ObjectName';
    case ByLine = 'By-line';
    case CopyrightNotice = 'CopyrightNotice';
    case CaptionAbstract = 'Caption-Abstract';

    // XMP fields
    case Creator = 'Creator';
    case Rights = 'Rights';
    case UsageTerms = 'UsageTerms';
    case Title = 'Title';
    case Description = 'Description';

    // EXIF fields
    case DateTime = 'DateTime';
    case DateTimeOriginal = 'DateTimeOriginal';
    case DateTimeDigitized = 'DateTimeDigitized';
    case GPSLatitude = 'GPSLatitude';
    case GPSLongitude = 'GPSLongitude';
    case Make = 'Make';
    case Model = 'Model';
    case Software = 'Software';
    case ImageType = 'ImageType';
    case ImageDescription = 'ImageDescription';
    case FileSize = 'FileSize';
    case ExifImageWidth = 'ExifImageWidth';
    case ExifImageLength = 'ExifImageLength';
    case XResolution = 'XResolution';
    case YResolution = 'YResolution';
    case ResolutionUnit = 'ResolutionUnit';
    case ShutterSpeedValue = 'ShutterSpeedValue';
    case ExposureTime = 'ExposureTime';
    case FocalLength = 'FocalLength';
    case FocalLengthIn35mmFilm = 'FocalLengthIn35mmFilm';
    case ApertureValue = 'ApertureValue';
    case FNumber = 'FNumber';
    case ISOSpeedRatings = 'ISOSpeedRatings';
    case ExposureBiasValue = 'ExposureBiasValue';
    case ExposureMode = 'ExposureMode';
    case ExposureProgram = 'ExposureProgram';
    case MeteringMode = 'MeteringMode';
    case Flash = 'Flash';
    case UserComment = 'UserComment';
    case ColorSpace = 'ColorSpace';
    case SensingMethod = 'SensingMethod';
    case WhiteBalance = 'WhiteBalance';
    case Orientation = 'Orientation';
    case Copyright = 'Copyright';
    case Artist = 'Artist';
    case LightSource = 'LightSource';
    case ImageStabilization = 'ImageStabilization';
    case SceneCaptureType = 'SceneCaptureType';

    // COMPOSITE fields
    case LensID = 'LensID';
    case Lens = 'Lens';
    case Aperture = 'Aperture';
    case DOF = 'DOF';
    case FOV = 'FOV';

    public function category(): MetadataCategory
    {
        return match ($this) {
            self::Keywords, self::ObjectName, self::ByLine,
            self::CopyrightNotice, self::CaptionAbstract
                => MetadataCategory::Iptc,

            self::Creator, self::Rights, self::UsageTerms,
            self::Title, self::Description
                => MetadataCategory::Xmp,

            self::LensID, self::Lens, self::Aperture,
            self::DOF, self::FOV
                => MetadataCategory::Composite,

            default => MetadataCategory::Exif,
        };
    }

    public function type(): FieldType
    {
        return match ($this) {
            self::Keywords => FieldType::Array_,

            self::DateTime, self::DateTimeOriginal, self::DateTimeDigitized
                => FieldType::Date,

            self::GPSLatitude, self::GPSLongitude => FieldType::Gps,

            self::FileSize, self::ExifImageWidth, self::ExifImageLength,
            self::XResolution, self::YResolution, self::ShutterSpeedValue,
            self::ExposureTime, self::FocalLength, self::FocalLengthIn35mmFilm,
            self::ApertureValue, self::FNumber, self::ISOSpeedRatings,
            self::ExposureBiasValue, self::ExposureMode, self::ExposureProgram,
            self::MeteringMode, self::Flash, self::ColorSpace,
            self::SensingMethod, self::WhiteBalance, self::Orientation,
            self::LightSource, self::SceneCaptureType
                => FieldType::Number,

            default => FieldType::Text,
        };
    }

    /**
     * @return list<self>
     */
    public static function forCategory(MetadataCategory $category): array
    {
        $fields = [];
        foreach (self::cases() as $field) {
            if ($field->category() === $category) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    /**
     * @return list<self>
     */
    public static function titleFields(): array
    {
        return [self::ObjectName, self::Title];
    }

    /**
     * @return list<self>
     */
    public static function descriptionFields(): array
    {
        return [self::CaptionAbstract, self::Description, self::ImageDescription];
    }
}
