<?php
/**
 * Ansel_Ajax_Imple_ToggleGalleryActions:: class for performing Ajax setting of
 * the gallery show_galleryactions user pref.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_ToggleGalleryActions extends Horde_Core_Ajax_Imple
{
    /**
     */
    protected function _attach($init)
    {
        if ($init) {
            $GLOBALS['page_output']->addScriptFile('togglewidget.js');
        }

        return 'doActionToggle("' . $this->_getDomId() . '", "show_actions")';
    }

    /**
     * Noop.
     */
    protected function _handle(Horde_Variables $vars)
    {
    }

}
