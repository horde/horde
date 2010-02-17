<?php
/**
 * ImageView to create the shadowsharpthumb view (sharp corners, shadowed)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageView_ShadowSharpThumb extends Ansel_ImageView
{
    public $need = array('DropShadow');

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
                $gal = $GLOBALS['ansel_storage']->getGallery($this->_image->gallery);
                $styleDef = $gal->getStyle();
            } else {
                $styleDef = Ansel::getStyleDefinition($this->_style);
            }

            try {
                $this->_image->addEffect('border', array('bordercolor' => '#333'));
                $this->_image->addEffect('DropShadow',
                                         array('background' => $styleDef['background'],
                                               'padding' => 5,
                                               'distance' => '8',
                                               'fade' => 2));
                $this->_image->applyEffects();
            } catch (Horde_Image_Exception $e) {
                return false;
            }

            return true;
        }
    }

}
