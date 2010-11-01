<?php
/**
 * ImageGenerator to create the screen view - image sized for slideshow view.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator_Screen extends Ansel_ImageGenerator
{
    /**
     *
     * @return boolean
     */
    protected function _create()
    {
        $this->_image->resize(min($GLOBALS['conf']['screen']['width'], $this->_dimensions['width']),
                              min($GLOBALS['conf']['screen']['height'], $this->_dimensions['height']),
                              true);
        if ($GLOBALS['conf']['screen']['unsharp'] && Ansel::isAvailable('Unsharpmask')) {
            try {
                $this->_image->addEffect('Unsharpmask',
                                         array('radius' => $GLOBALS['conf']['screen']['radius'],
                                               'threshold' => $GLOBALS['conf']['screen']['threshold'],
                                               'amount' => $GLOBALS['conf']['screen']['amount']));
                $this->_image->applyEffects();
            } catch (Horde_Image $e) {
                throw new Ansel_Exception($e);
            }
        }

        return $this->_image->getHordeImage();
    }

}
