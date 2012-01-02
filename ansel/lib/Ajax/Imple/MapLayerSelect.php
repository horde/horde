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
class Ansel_Ajax_Imple_MapLayerSelect extends Horde_Core_Ajax_Imple
{
    public function attach()
    {
        // noop
    }

    public function getUrl()
    {
        return $this->_getUrl('MapLayerSelect', 'ansel', array('sessionWrite' => true));
    }

    public function handle($args, $post)
    {
         if (!isset($post['name'])) {
            return 0;
         }
        $GLOBALS['prefs']->setValue('current_maplayer', $post['name']);
        //return $GLOBALS['prefs']->getValue('current_maplayer');
        return 1;
    }

}
