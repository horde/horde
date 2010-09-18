<?php
/**
 * ImageGenerator to create the thumb view (plain, resized thumbnails).
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator_Thumb extends Ansel_ImageGenerator
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->title = _("Basic Thumbnails");
    }

    /**
     *
     * @return Horde_Image
     */
    protected function _create()
    {
        $this->_image->resize(min($GLOBALS['conf']['thumbnail']['width'], $this->_dimensions['width']),
                              min($GLOBALS['conf']['thumbnail']['height'], $this->_dimensions['height']),
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
