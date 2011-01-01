<?php
/**
 * ImageGenerator to create a square thumbnail.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator_SquareThumb extends Ansel_ImageGenerator
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->title = _("Square Thumbnails");
    }

    /**
     *
     * @return Horde_Image
     */
    protected function _create()
    {
        // Take the largest requested dimension
        if (empty($this->_params['width'])) {
            $size = min($GLOBALS['conf']['thumbnail']['height'], $GLOBALS['conf']['thumbnail']['width']);
        } else {
            $size = min($this->_params['width'], $this->_params['height']);
        }

        // Use smartcrop algorithm if we have it, otherwise a plain center crop.
        if (Ansel::isAvailable('SmartCrop') && $GLOBALS['conf']['image']['smartcrop']) {
            $this->_image->addEffect('SmartCrop', array('width' => $size, 'height' => $size));
        } else {
            $this->_image->addEffect('CenterCrop', array('width' => $size, 'height' => $size));
        }
        $this->_image->applyEffects();

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
