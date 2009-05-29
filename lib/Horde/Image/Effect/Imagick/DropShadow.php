<?php
/**
 * Image effect for adding a drop shadow.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Imagick_DropShadow extends Horde_Image_Effect
{
    /**
     * Valid parameters: Most are currently ignored for the im version
     * of this effect.
     *
     * @TODO
     *
     * @var array
     */
    protected $_params = array('distance' => 5, // This is used as the x and y offset
                               'width' => 2,
                               'hexcolor' => '000000',
                               'angle' => 215,
                               'fade' => 3, // Sigma value
                               'padding' => 0,
                               'background' => 'none');

    /**
     * Apply the effect.
     *
     * @return mixed true | PEAR_Error
     */
    public function apply()
    {
        $shadow = $this->_image->imagick->clone();
        $shadow->setImageBackgroundColor(new ImagickPixel('black'));
        $shadow->shadowImage(80, $this->_params['fade'],
                             $this->_params['distance'],
                             $this->_params['distance']);


        // If we have an actual background color, we need to explicitly
        // create a new background image with that color to be sure there
        // *is* a background color.
        if ($this->_params['background'] != 'none') {
            $size = $shadow->getImageGeometry();
            $new = new Imagick();
            $new->newImage($size['width'], $size['height'], new ImagickPixel($this->_params['background']));
            $new->setImageFormat($this->_image->getType());

            $new->compositeImage($shadow, Imagick::COMPOSITE_OVER, 0, 0);
            $shadow->clear();
            $shadow->addImage($new);
            $new->destroy();
        }

        if ($this->_params['padding']) {
            Horde_Image_Imagick::borderImage($shadow,
                                             $this->_params['background'],
                                             $this->_params['padding'],
                                             $this->_params['padding']);
        }

        $shadow->compositeImage($this->_image->imagick, Imagick::COMPOSITE_OVER, 0, 0);
        $this->_image->imagick->clear();
        $this->_image->imagick->addImage($shadow);
        $shadow->destroy();

        $this->_image->clearGeometry();

        return true;
    }

}