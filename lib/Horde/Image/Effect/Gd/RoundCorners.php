<?php

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    James Heinrich <info@silisoftware.com>
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */

/**
 * Image effect for round image corners.
 *
 * This algorithm is from the phpThumb project available at
 * http://phpthumb.sourceforge.net and all credit for this script should go to
 * James Heinrich <info@silisoftware.com>.  Modifications made to the code to
 * fit it within the Horde framework and to adjust for our coding standards.
 *
 * @author    James Heinrich <info@silisoftware.com>
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Gd_RoundCorners extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *   - radius: (integer) Radius of rounded corners.
     *
     * @var array
     */
    protected $_params = ['radius' => 10];

    /**
     * Applies the effect.
     */
    public function apply()
    {
        // Original comments from phpThumb project:
        // generate mask at twice desired resolution and downsample afterwards
        // for easy antialiasing mask is generated as a white double-size
        // elipse on a triple-size black background and copy-paste-resampled
        // onto a correct-size mask image as 4 corners due to errors when the
        // entire mask is resampled at once (gray edges)
        $radius_x = $radius_y = $this->_params['radius'];
        $gdimg = $this->_image->_im;
        $imgX = round($this->_image->call('imageSX', [$gdimg]));
        $imgY = round($this->_image->call('imageSY', [$gdimg]));
        $maskTriple = $this->_image->create(
            round($radius_x * 6),
            round($radius_y * 6)
        );
        $mask = $this->_image->create($imgX, $imgY);
        $color_transparent = $this->_image->call(
            'imageColorAllocate',
            [$maskTriple, 255, 255, 255]
        );

        $this->_image->call(
            'imageFilledEllipse',
            [
                $maskTriple,
                $radius_x * 3, $radius_y * 3,
                $radius_x * 4, $radius_y * 4,
                $color_transparent,
            ]
        );

        $this->_image->call(
            'imageFilledRectangle',
            [$mask, 0, 0, $imgX, $imgY, $color_transparent]
        );

        $this->_image->call(
            'imageCopyResampled',
            [
                $mask, $maskTriple,
                0, 0, $radius_x, $radius_y,
                $radius_x, $radius_y, $radius_x * 2, $radius_y * 2,
            ]
        );
        $this->_image->call(
            'imageCopyResampled',
            [
                $mask, $maskTriple,
                0, $imgY - $radius_y,
                $radius_x, $radius_y * 3,
                $radius_x, $radius_y,
                $radius_x * 2, $radius_y * 2,
            ]
        );
        $this->_image->call(
            'imageCopyResampled',
            [
                $mask, $maskTriple,
                $imgX - $radius_x, $imgY - $radius_y,
                $radius_x * 3, $radius_y * 3,
                $radius_x, $radius_y,
                $radius_x * 2, $radius_y * 2,
            ]
        );
        $this->_image->call(
            'imageCopyResampled',
            [
                $mask, $maskTriple,
                $imgX - $radius_x, 0,
                $radius_x * 3, $radius_y,
                $radius_x, $radius_y,
                $radius_x * 2, $radius_y * 2,
            ]
        );

        $this->_image->applyMask($mask);
        $this->_image->call('imageDestroy', [$mask]);
        $this->_image->call('imageDestroy', [$maskTriple]);
    }
}
