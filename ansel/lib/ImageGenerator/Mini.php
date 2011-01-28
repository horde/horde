<?php
/**
 * ImageGenerator to create the mini view.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.

 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator_Mini extends Ansel_ImageGenerator
{
    /**
     *
     * @return Horde_Image
     */
    protected function _create()
    {
        if ($GLOBALS['conf']['image']['squaremini']) {
            $generator = Ansel_ImageGenerator::factory('SquareThumb', array('width' => min(50, $this->_dimensions['width']),
                                                                            'height' => min(50, $this->_dimensions['height']),
                                                                            'image' => $this->_image,
                                                                            'style' => $this->_params['style']));
            return $generator->create();
        } else {
            $this->_image->resize(min(50, $this->_dimensions['width']),
                                  min(50, $this->_dimensions['height']),
                                  true);
            if ($GLOBALS['conf']['thumbnail']['unsharp'] && Ansel::isAvailable('Unsharpmask')) {
                try {
                    $this->_image->addEffect('Unsharpmask',
                                             array('radius' => $GLOBALS['conf']['thumbnail']['radius'],
                                                   'threshold' => $GLOBALS['conf']['thumbnail']['threshold'],
                                                   'amount' => $GLOBALS['conf']['thumbnail']['amount']));
                    $this->_image->applyEffects();
                } catch (Horde_Image_Exception $e) {
                    throw new Ansel_Exception($e);
                }
            }

            return $this->_image->getHordeImage();
        }
    }

}
