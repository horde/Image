<?php

/**
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */

/**
 * This class provides some utility functions, such as generating highlights
 * of a color.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image
{
    /**
     * Calculates a lighter (or darker) version of a color.
     *
     * @param string $color    An HTML color, e.g.: #ffffcc.
     * @param integer $factor  The brightness difference between -0xff and
     *                         +0xff. Plus values raise the brightness,
     *                         negative values reduce it.
     *
     * @return string  A modified HTML color.
     */
    public static function modifyColor($color, $factor = 0x11)
    {
        [$r, $g, $b] = self::getColor($color);

        $r = min(max($r + $factor, 0), 255);
        $g = min(max($g + $factor, 0), 255);
        $b = min(max($b + $factor, 0), 255);

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Calculates a more intense version of a color.
     *
     * @param string $color    An HTML color, e.g.: #ffffcc.
     * @param integer $factor  The intensity difference between -0xff and
     *                         +0xff. Plus values raise the intensity,
     *                         negative values reduce it.
     *
     * @return string  A more intense HTML color.
     */
    public static function moreIntenseColor($color, $factor = 0x11)
    {
        [$r, $g, $b] = self::getColor($color);

        if ($r >= $g && $r >= $b) {
            $g = $g / $r;
            $b = $b / $r;

            $r += $factor;
            $g = floor($g * $r);
            $b = floor($b * $r);
        } elseif ($g >= $r && $g >= $b) {
            $r = $r / $g;
            $b = $b / $g;

            $g += $factor;
            $r = floor($r * $g);
            $b = floor($b * $g);
        } else {
            $r = $r / $b;
            $g = $g / $b;

            $b += $factor;
            $r = floor($r * $b);
            $g = floor($g * $b);
        }

        $r = min(max($r, 0), 255);
        $g = min(max($g, 0), 255);
        $b = min(max($b, 0), 255);

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Returns the brightness of a color.
     *
     * @param string $color  An HTML color, e.g.: #ffffcc.
     *
     * @return integer  The brightness on a scale of 0 to 255.
     */
    public static function brightness($color)
    {
        [$r, $g, $b] = self::getColor($color);
        return round((($r * 299) + ($g * 587) + ($b * 114)) / 1000);
    }

    /**
     * Returns the WCAG relative luminance of a color (0.0 - 1.0).
     *
     * @see https://www.w3.org/TR/WCAG21/#dfn-relative-luminance
     *
     * @param string $color  An HTML color, e.g.: #ffffcc.
     *
     * @return float  The relative luminance.
     */
    public static function relativeLuminance($color)
    {
        [$r, $g, $b] = self::getColor($color);
        $f = function ($c) {
            $c /= 255;
            return ($c <= 0.03928)
                ? $c / 12.92
                : pow(($c + 0.055) / 1.055, 2.4);
        };

        return (0.2126 * $f($r)) + (0.7152 * $f($g)) + (0.0722 * $f($b));
    }

    /**
     * Picks the foreground color that contrasts best with a background,
     * using the WCAG contrast-ratio formula.
     *
     * This is more accurate than thresholding brightness(): for some
     * saturated mid-luminance colors a naive brightness threshold picks the
     * lower-contrast option (e.g. white on bright magenta). Comparing the
     * actual WCAG contrast ratio against both candidates always picks the
     * more readable one.
     *
     * @param string $bg     The background color, e.g.: #ff00ee.
     * @param string $light  Candidate light foreground (default white).
     * @param string $dark   Candidate dark foreground (default black).
     *
     * @return string  Either $light or $dark, whichever contrasts best.
     */
    public static function contrastColor($bg, $light = '#fff', $dark = '#000')
    {
        $lbg = self::relativeLuminance($bg);
        $ratio = function ($l1, $l2) {
            $hi = max($l1, $l2);
            $lo = min($l1, $l2);
            return ($hi + 0.05) / ($lo + 0.05);
        };

        $cLight = $ratio($lbg, self::relativeLuminance($light));
        $cDark = $ratio($lbg, self::relativeLuminance($dark));

        return ($cLight >= $cDark) ? $light : $dark;
    }

    /**
     * Calculates the grayscale value of a color.
     *
     * @param integer $r  A red value.
     * @param integer $g  A green value.
     * @param integer $b  A blue value.
     *
     * @return integer  The grayscale value of the color.
     */
    public static function grayscaleValue($r, $g, $b)
    {
        return round(($r * 0.30) + ($g * 0.59) + ($b * 0.11));
    }

    /**
     * Turns an RGB value into grayscale.
     *
     * @param integer[] $originalPixel  A hash with 'red', 'green', and 'blue'
     *                                  values.
     *
     * @return integer[]  A hash with 'red', 'green', and 'blue' values for the
     *                    corresponding gray color.
     */
    public static function grayscalePixel($originalPixel)
    {
        $gray = Horde_Image::grayscaleValue(
            $originalPixel['red'],
            $originalPixel['green'],
            $originalPixel['blue']
        );
        return ['red' => $gray, 'green' => $gray, 'blue' => $gray];
    }

    /**
     * Normalizes an HTML color.
     *
     * @param string $color  An HTML color, e.g.: #ffffcc or #ffc.
     *
     * @return integer[]  Array with three elements: red, green, and blue.
     */
    public static function getColor($color)
    {
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        if (strlen($color) == 3) {
            $color = str_repeat($color[0], 2)
                . str_repeat($color[1], 2)
                . str_repeat($color[2], 2);
        }

        return [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        ];
    }

    /**
     * Returns the RGB values for an HTML color name.
     *
     * @param string $colorname  A color name.
     *
     * @return array  An array of RGB values.
     */
    public static function getRGB($colorname)
    {
        return Horde_Image_Rgb::$colors[$colorname]
            ?? [0, 0, 0];
    }

    /**
     * Returns the hexadecimal representation of an HTML color name.
     *
     * @param string $colorname  A color name.
     *
     * @return string  The hex representation of the color.
     */
    public static function getHexColor($colorname)
    {
        [$r, $g, $b] = self::getRGB($colorname);
        return '#' . str_pad(dechex(min($r, 255)), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex(min($g, 255)), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex(min($b, 255)), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Returns an x,y pair on circle, assuming center is 0,0.
     *
     * @param float $degrees     The degrees of arc to get the point for.
     * @param integer $diameter  The diameter of the circle.
     *
     * @return array  (x coordinate, y coordinate) of the point.
     */
    public static function circlePoint($degrees, $diameter)
    {
        // Avoid problems with floats.
        $degrees += 0.0001;

        return [cos(deg2rad($degrees)) * ($diameter / 2),
            sin(deg2rad($degrees)) * ($diameter / 2)];
    }

    /**
     * Returns point coordinates at the limits of an arc.
     *
     * @param integer $r      The radius of the arc.
     * @param integer $start  The starting angle.
     * @param integer $end    The ending angle.
     *
     * @return array  The start point (x1,y1), end point (x2,y2), and anchor
     *                point (x3,y3).
     */
    public static function arcPoints($r, $start, $end)
    {
        // Start point.
        $pts['x1'] = $r * cos(deg2rad($start));
        $pts['y1'] = $r * sin(deg2rad($start));

        // End point.
        $pts['x2'] = $r * cos(deg2rad($end));
        $pts['y2'] = $r * sin(deg2rad($end));

        // Anchor point.
        $pts['x3'] = $pts['y3'] = 0;

        // Shift to positive.
        if ($pts['x1'] < 0) {
            $pts['x2'] += abs($pts['x1']);
            $pts['x3'] += abs($pts['x1']);
            $pts['x1'] = 0;
        }
        if ($pts['x2'] < 0) {
            $pts['x1'] += abs($pts['x2']);
            $pts['x3'] += abs($pts['x2']);
            $pts['x2'] = 0;
        }
        if ($pts['x3'] < 0) {
            $pts['x1'] += abs($pts['x3']);
            $pts['x2'] += abs($pts['x3']);
            $pts['x3'] = 0;
        }
        if ($pts['y1'] < 0) {
            $pts['y2'] += abs($pts['y1']);
            $pts['y3'] += abs($pts['y1']);
            $pts['y1'] = 0;
        }
        if ($pts['y2'] < 0) {
            $pts['y1'] += abs($pts['y2']);
            $pts['y3'] += abs($pts['y2']);
            $pts['y2'] = 0;
        }
        if ($pts['y3'] < 0) {
            $pts['y1'] += abs($pts['y3']);
            $pts['y2'] += abs($pts['y3']);
            $pts['y3'] = 0;
        }

        return $pts;
    }

    /**
     * Returns the point size for an HTML font size name.
     */
    public static function getFontSize($fontsize)
    {
        switch ($fontsize) {
            case 'medium':
                return 18;
            case 'large':
                return 24;
            case 'giant':
                return 30;
            default:
                return 12;
        }
    }

}
