<?php
/**
 * Ansel_Ajax_Imple_ToggleOtherGalleries:: class for performing Ajax setting of
 * the gallery show_actions user pref.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_ToggleOtherGalleries extends Horde_Core_Ajax_Imple
{
    /**
     */
    protected function _attach($init)
    {
        if ($init) {
            $GLOBALS['page_output']->addScriptFile('togglewidget.js');
        }

        return 'doActionToggle("' . $this->_getDomId() . '", "show_othergalleries")';
    }

    /**
     * Noop.
     */
    protected function _handle(Horde_Variables $vars)
    {
    }

}
