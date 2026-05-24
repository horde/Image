<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Effect;

use Horde\Image\Color\Color;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\Driver\ImagickResource;
use Horde\Image\DriverException;
use Horde\Image\Effect\TextWatermark;
use Horde\Image\Effect\WatermarkPosition;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextWatermark::class)]
#[CoversClass(WatermarkPosition::class)]
#[RequiresPhpExtension('imagick')]
final class TextWatermarkTest extends TestCase
{
    private ImagickDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ImagickDriver();
    }

    private function createImage(float $width = 200.0, float $height = 150.0): ImagickResource
    {
        return $this->driver->create(new Size($width, $height), Color::rgb(0.2, 0.2, 0.2));
    }

    public function testDefaultConstruction(): void
    {
        $effect = new TextWatermark('© Test');

        $this->assertSame('© Test', $effect->text);
        $this->assertSame(WatermarkPosition::BottomRight, $effect->position);
        $this->assertSame(24.0, $effect->fontSize);
        $this->assertNull($effect->fontFamily);
        $this->assertNull($effect->fontFile);
        $this->assertSame(0.0, $effect->angle);
        $this->assertSame(10, $effect->padding);
        $this->assertSame(0.5, $effect->opacity);
    }

    public function testCustomConstruction(): void
    {
        $color = Color::rgb(1.0, 0.0, 0.0);
        $effect = new TextWatermark(
            text: 'SAMPLE',
            position: WatermarkPosition::Center,
            color: $color,
            fontSize: 48.0,
            fontFamily: 'Courier',
            angle: -45.0,
            padding: 20,
            opacity: 0.8,
        );

        $this->assertSame('SAMPLE', $effect->text);
        $this->assertSame(WatermarkPosition::Center, $effect->position);
        $this->assertSame(48.0, $effect->fontSize);
        $this->assertSame('Courier', $effect->fontFamily);
        $this->assertSame(-45.0, $effect->angle);
        $this->assertSame(20, $effect->padding);
        $this->assertSame(0.8, $effect->opacity);
        $this->assertSame(1.0, $effect->color()->red());
    }

    public function testDefaultColorIsWhite(): void
    {
        $effect = new TextWatermark('test');
        $this->assertSame(1.0, $effect->color()->red());
        $this->assertSame(1.0, $effect->color()->green());
        $this->assertSame(1.0, $effect->color()->blue());
    }

    public function testApplyPreservesDimensions(): void
    {
        $image = $this->createImage(300.0, 200.0);
        $effect = new TextWatermark('Watermark');

        $result = $effect->apply($image);

        $this->assertSame(300.0, $result->size()->width);
        $this->assertSame(200.0, $result->size()->height);
    }

    public function testApplyReturnsNewInstance(): void
    {
        $image = $this->createImage();
        $effect = new TextWatermark('Test');

        $result = $effect->apply($image);

        $this->assertNotSame($image, $result);
    }

    public function testApplyModifiesImageData(): void
    {
        $image = $this->createImage(200.0, 100.0);
        $effect = new TextWatermark('WATERMARK', opacity: 1.0, fontSize: 36.0);

        $result = $effect->apply($image);

        $originalBlob = $image->imagick()->getImageBlob();
        $resultBlob = $result->imagick()->getImageBlob();
        $this->assertNotSame($originalBlob, $resultBlob);
    }

    #[DataProvider('positionProvider')]
    public function testAllPositionsApplySuccessfully(WatermarkPosition $position): void
    {
        $image = $this->createImage(300.0, 200.0);
        $effect = new TextWatermark('Test', position: $position);

        $result = $effect->apply($image);

        $this->assertSame(300.0, $result->size()->width);
        $this->assertSame(200.0, $result->size()->height);
    }

    public static function positionProvider(): array
    {
        $cases = [];
        foreach (WatermarkPosition::cases() as $pos) {
            $cases[$pos->value] = [$pos];
        }
        return $cases;
    }

    public function testTileModeFillsImage(): void
    {
        $image = $this->createImage(400.0, 300.0);
        $effect = new TextWatermark(
            text: 'SAMPLE',
            position: WatermarkPosition::Tile,
            fontSize: 16.0,
            opacity: 0.3,
        );

        $result = $effect->apply($image);

        $this->assertSame(400.0, $result->size()->width);
        $this->assertSame(300.0, $result->size()->height);
    }

    public function testAngleAppliesWithoutError(): void
    {
        $image = $this->createImage();
        $effect = new TextWatermark('Diagonal', angle: -30.0, position: WatermarkPosition::Center);

        $result = $effect->apply($image);

        $this->assertSame(200.0, $result->size()->width);
    }

    public function testZeroOpacityProducesInvisibleWatermark(): void
    {
        $image = $this->createImage(100.0, 100.0);
        $effect = new TextWatermark('Invisible', opacity: 0.0);

        $result = $effect->apply($image);

        $this->assertSame(100.0, $result->size()->width);
    }

    public function testFullOpacityApplies(): void
    {
        $image = $this->createImage(100.0, 100.0);
        $effect = new TextWatermark('Solid', opacity: 1.0);

        $result = $effect->apply($image);

        $this->assertSame(100.0, $result->size()->width);
    }

    public function testEffectInterfaceViaImageResource(): void
    {
        $image = $this->createImage();
        $effect = new TextWatermark('Via effect()', position: WatermarkPosition::TopLeft);

        $result = $image->effect($effect);

        $this->assertInstanceOf(ImagickResource::class, $result);
        $this->assertNotSame($image, $result);
    }

    public function testWatermarkPositionEnumValues(): void
    {
        $this->assertSame('top-left', WatermarkPosition::TopLeft->value);
        $this->assertSame('bottom-right', WatermarkPosition::BottomRight->value);
        $this->assertSame('center', WatermarkPosition::Center->value);
        $this->assertSame('tile', WatermarkPosition::Tile->value);
        $this->assertCount(10, WatermarkPosition::cases());
    }
}
