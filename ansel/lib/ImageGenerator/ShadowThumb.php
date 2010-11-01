<?php
/**
 * ImageGenerator to create the shadowsharpthumb view (sharp corners, shadowed)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator_ShadowThumb extends Ansel_ImageGenerator
{
    public $need = array('DropShadow');

    public function __construct($params)
    {
        parent::__construct($params);
        $this->title = _("Drop Shadows");
    }

    /**
     *
     * @return boolean
     */
    protected function _create()
    {
        $this->_image->resize(min($GLOBALS['conf']['thumbnail']['width'], $this->_dimensions['width']),
                              min($GLOBALS['conf']['thumbnail']['height'], $this->_dimensions['height']),
                              true);

        /* Don't bother with these effects for a stack image
         * (which will have a negative gallery_id). */
        if ($this->_image->gallery > 0) {
            if (is_null($this->_style)) {
                $gal = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($this->_image->gallery);
                $styleDef = $gal->getStyle();
            } else {
                $styleDef = $this->_style;
            }

            try {
                $this->_image->addEffect('Border', array('bordercolor' => '#333', 'borderwidth' => 1));
                $this->_image->addEffect('DropShadow',
                                         array('background' => $styleDef->background,
                                               'padding' => 5,
                                               'distance' => 8,
                                               'fade' => 2));

                if ($GLOBALS['conf']['thumbnail']['unsharp'] && Ansel::isAvailable('Unsharpmask')) {
                    $this->_image->addEffect('Unsharpmask',
                                             array('radius' => $GLOBALS['conf']['thumbnail']['radius'],
                                                   'threshold' => $GLOBALS['conf']['thumbnail']['threshold'],
                                                   'amount' => $GLOBALS['conf']['thumbnail']['amount']));
                    $this->_image->applyEffects();
                }

                $this->_image->applyEffects();
            } catch (Horde_Image_Exception $e) {
                throw new Ansel_Exception($e);
            }

            return $this->_image->getHordeImage();
        }
    }

}
