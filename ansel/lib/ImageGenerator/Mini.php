<?php
/**
 * ImageGenerator to create the mini view.
 *
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
