<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Drawing;

use Horde\Image\Color\Color;
use Horde\Image\Drawing\Brush;
use Horde\Image\Drawing\BrushShape;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\Drawing\LineCap;
use Horde\Image\Drawing\LineDashPattern;
use Horde\Image\Geometry\AffineTransform;
use Horde\Image\Geometry\Point;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Brush::class)]
#[CoversClass(BrushShape::class)]
final class BrushTest extends TestCase
{
    private RecordingContext $ctx;

    protected function setUp(): void
    {
        $this->ctx = new RecordingContext();
    }

    public function testSquareBrush(): void
    {
        Brush::draw($this->ctx, new Point(10.0, 20.0), Color::rgb(1.0, 0.0, 0.0), BrushShape::Square);

        self::assertTrue($this->ctx->hasCalled('save'));
        self::assertTrue($this->ctx->hasCalled('setFillColor'));
        self::assertTrue($this->ctx->hasCalled('rect'));
        self::assertTrue($this->ctx->hasCalled('fill'));
        self::assertTrue($this->ctx->hasCalled('restore'));
    }

    public function testCircleBrush(): void
    {
        Brush::draw($this->ctx, new Point(5.0, 5.0), Color::rgb(0.0, 1.0, 0.0), BrushShape::Circle);

        self::assertTrue($this->ctx->hasCalled('moveTo'));
        self::assertTrue($this->ctx->hasCalled('lineTo'));
        self::assertTrue($this->ctx->hasCalled('closePath'));
        self::assertTrue($this->ctx->hasCalled('fill'));
    }

    public function testDiamondBrush(): void
    {
        Brush::draw($this->ctx, new Point(0.0, 0.0), Color::rgb(0.0, 0.0, 1.0), BrushShape::Diamond);

        self::assertTrue($this->ctx->hasCalled('moveTo'));
        self::assertTrue($this->ctx->hasCalled('lineTo'));
        self::assertTrue($this->ctx->hasCalled('closePath'));
        self::assertTrue($this->ctx->hasCalled('fill'));
    }

    public function testTriangleBrush(): void
    {
        Brush::draw($this->ctx, new Point(50.0, 50.0), Color::rgb(1.0, 1.0, 0.0), BrushShape::Triangle);

        self::assertTrue($this->ctx->hasCalled('moveTo'));
        self::assertTrue($this->ctx->hasCalled('lineTo'));
        self::assertTrue($this->ctx->hasCalled('closePath'));
        self::assertTrue($this->ctx->hasCalled('fill'));
    }

    public function testCustomSize(): void
    {
        Brush::draw($this->ctx, new Point(10.0, 10.0), Color::rgb(0.0, 0.0, 0.0), BrushShape::Square, 8.0);

        $rectCall = $this->ctx->getCall('rect');
        self::assertNotNull($rectCall);
        self::assertSame(6.0, $rectCall[0]);
        self::assertSame(6.0, $rectCall[1]);
        self::assertSame(8.0, $rectCall[2]);
        self::assertSame(8.0, $rectCall[3]);
    }

    public function testDefaultShapeIsSquare(): void
    {
        Brush::draw($this->ctx, new Point(10.0, 10.0), Color::rgb(0.0, 0.0, 0.0));

        self::assertTrue($this->ctx->hasCalled('rect'));
    }
}

/**
 * @internal Test double that records method calls
 */
final class RecordingContext implements DrawingContext
{
    /** @var array<string, list<list<mixed>>> */
    private array $calls = [];

    public function hasCalled(string $method): bool
    {
        return isset($this->calls[$method]);
    }

    /** @return list<mixed>|null */
    public function getCall(string $method, int $index = 0): ?array
    {
        return $this->calls[$method][$index] ?? null;
    }

    private function record(string $method, mixed ...$args): static
    {
        $this->calls[$method][] = $args;
        return $this;
    }

    public function save(): static
    {
        return $this->record('save');
    }
    public function restore(): static
    {
        return $this->record('restore');
    }
    public function setFillColor(Color $color): static
    {
        return $this->record('setFillColor', $color);
    }
    public function setStrokeColor(Color $color): static
    {
        return $this->record('setStrokeColor', $color);
    }
    public function setLineWidth(float $width): static
    {
        return $this->record('setLineWidth', $width);
    }
    public function setLineCap(LineCap $cap): static
    {
        return $this->record('setLineCap', $cap);
    }
    public function setDashPattern(LineDashPattern $pattern): static
    {
        return $this->record('setDashPattern', $pattern);
    }
    public function moveTo(float $x, float $y): static
    {
        return $this->record('moveTo', $x, $y);
    }
    public function lineTo(float $x, float $y): static
    {
        return $this->record('lineTo', $x, $y);
    }
    public function curveTo(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): static
    {
        return $this->record('curveTo', $x1, $y1, $x2, $y2, $x3, $y3);
    }
    public function closePath(): static
    {
        return $this->record('closePath');
    }
    public function rect(float $x, float $y, float $w, float $h): static
    {
        return $this->record('rect', $x, $y, $w, $h);
    }
    public function stroke(): static
    {
        return $this->record('stroke');
    }
    public function fill(): static
    {
        return $this->record('fill');
    }
    public function fillAndStroke(): static
    {
        return $this->record('fillAndStroke');
    }
    public function clip(): static
    {
        return $this->record('clip');
    }
    public function transform(AffineTransform $transform): static
    {
        return $this->record('transform', $transform);
    }
}
