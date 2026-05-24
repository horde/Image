<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Metadata;

use Horde\Image\Metadata\FieldType;
use Horde\Image\Metadata\MetadataCategory;
use Horde\Image\Metadata\MetadataField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetadataField::class)]
final class MetadataFieldTest extends TestCase
{
    public function testIptcFieldsReturnIptcCategory(): void
    {
        $this->assertSame(MetadataCategory::Iptc, MetadataField::Keywords->category());
        $this->assertSame(MetadataCategory::Iptc, MetadataField::ObjectName->category());
        $this->assertSame(MetadataCategory::Iptc, MetadataField::ByLine->category());
        $this->assertSame(MetadataCategory::Iptc, MetadataField::CopyrightNotice->category());
        $this->assertSame(MetadataCategory::Iptc, MetadataField::CaptionAbstract->category());
    }

    public function testXmpFieldsReturnXmpCategory(): void
    {
        $this->assertSame(MetadataCategory::Xmp, MetadataField::Creator->category());
        $this->assertSame(MetadataCategory::Xmp, MetadataField::Rights->category());
        $this->assertSame(MetadataCategory::Xmp, MetadataField::Title->category());
        $this->assertSame(MetadataCategory::Xmp, MetadataField::Description->category());
    }

    public function testCompositeFieldsReturnCompositeCategory(): void
    {
        $this->assertSame(MetadataCategory::Composite, MetadataField::LensID->category());
        $this->assertSame(MetadataCategory::Composite, MetadataField::Lens->category());
        $this->assertSame(MetadataCategory::Composite, MetadataField::DOF->category());
    }

    public function testExifFieldsDefaultToExifCategory(): void
    {
        $this->assertSame(MetadataCategory::Exif, MetadataField::Make->category());
        $this->assertSame(MetadataCategory::Exif, MetadataField::Model->category());
        $this->assertSame(MetadataCategory::Exif, MetadataField::FNumber->category());
        $this->assertSame(MetadataCategory::Exif, MetadataField::ISOSpeedRatings->category());
    }

    public function testKeywordsIsArrayType(): void
    {
        $this->assertSame(FieldType::Array_, MetadataField::Keywords->type());
    }

    public function testDateFieldsAreDateType(): void
    {
        $this->assertSame(FieldType::Date, MetadataField::DateTime->type());
        $this->assertSame(FieldType::Date, MetadataField::DateTimeOriginal->type());
        $this->assertSame(FieldType::Date, MetadataField::DateTimeDigitized->type());
    }

    public function testGpsFieldsAreGpsType(): void
    {
        $this->assertSame(FieldType::Gps, MetadataField::GPSLatitude->type());
        $this->assertSame(FieldType::Gps, MetadataField::GPSLongitude->type());
    }

    public function testNumericFields(): void
    {
        $this->assertSame(FieldType::Number, MetadataField::FNumber->type());
        $this->assertSame(FieldType::Number, MetadataField::ISOSpeedRatings->type());
        $this->assertSame(FieldType::Number, MetadataField::ExposureTime->type());
        $this->assertSame(FieldType::Number, MetadataField::Flash->type());
        $this->assertSame(FieldType::Number, MetadataField::FileSize->type());
    }

    public function testTextFieldsAreDefault(): void
    {
        $this->assertSame(FieldType::Text, MetadataField::Make->type());
        $this->assertSame(FieldType::Text, MetadataField::Model->type());
        $this->assertSame(FieldType::Text, MetadataField::Software->type());
        $this->assertSame(FieldType::Text, MetadataField::Lens->type());
    }

    public function testForCategoryReturnsCorrectFields(): void
    {
        $iptcFields = MetadataField::forCategory(MetadataCategory::Iptc);
        $this->assertCount(5, $iptcFields);
        $this->assertContains(MetadataField::Keywords, $iptcFields);
        $this->assertContains(MetadataField::ObjectName, $iptcFields);

        $xmpFields = MetadataField::forCategory(MetadataCategory::Xmp);
        $this->assertCount(5, $xmpFields);
        $this->assertContains(MetadataField::Title, $xmpFields);

        $compositeFields = MetadataField::forCategory(MetadataCategory::Composite);
        $this->assertCount(5, $compositeFields);
        $this->assertContains(MetadataField::LensID, $compositeFields);
    }

    public function testTitleFields(): void
    {
        $fields = MetadataField::titleFields();
        $this->assertSame([MetadataField::ObjectName, MetadataField::Title], $fields);
    }

    public function testDescriptionFields(): void
    {
        $fields = MetadataField::descriptionFields();
        $this->assertSame(
            [MetadataField::CaptionAbstract, MetadataField::Description, MetadataField::ImageDescription],
            $fields,
        );
    }

    public function testAllCasesHaveStringValues(): void
    {
        foreach (MetadataField::cases() as $field) {
            $this->assertNotEmpty($field->value);
        }
    }
}
