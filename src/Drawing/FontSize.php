<?php

declare(strict_types=1);

namespace Horde\Image\Drawing;

enum FontSize: string
{
    case Tiny = 'tiny';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Giant = 'giant';

    public function points(): float
    {
        return match ($this) {
            self::Tiny => 8.0,
            self::Small => 12.0,
            self::Medium => 18.0,
            self::Large => 24.0,
            self::Giant => 30.0,
        };
    }

    /**
     * Return the named size whose point value is nearest to the given value.
     */
    public static function fromPoints(float $points): self
    {
        $best = self::Small;
        $bestDiff = PHP_FLOAT_MAX;

        foreach (self::cases() as $case) {
            $diff = abs($case->points() - $points);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $best = $case;
            }
        }

        return $best;
    }

    /**
     * Return the smallest named size whose point value is strictly greater than the given value.
     */
    public static function nextUp(float $points): ?self
    {
        $best = null;

        foreach (self::cases() as $case) {
            if ($case->points() > $points) {
                if ($best === null || $case->points() < $best->points()) {
                    $best = $case;
                }
            }
        }

        return $best;
    }

    /**
     * Return the largest named size whose point value is strictly less than the given value.
     */
    public static function nextDown(float $points): ?self
    {
        $best = null;

        foreach (self::cases() as $case) {
            if ($case->points() < $points) {
                if ($best === null || $case->points() > $best->points()) {
                    $best = $case;
                }
            }
        }

        return $best;
    }
}
