<?php
/**
 * Ansel_Ajax_Imple_ToggleGalleryActions:: class for performing Ajax setting of
 * the gallery show_galleryactions user pref.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_ToggleGalleryActions extends Horde_Core_Ajax_Imple
{
    public function attach()
    {
        // Include the js
        Horde::addScriptFile('togglewidget.js');

        $url = $this->_getUrl('ToggleGalleryActions', 'ansel', array('post' => 'value', 'sessionWrite' => true));

        $js = array();
        $js[] = "Ansel.widgets['galleryActions'] = {'bindTo': '" . $this->_params['bindTo'] . "', 'url': '" . $url . "'}";
        $js[] = "Event.observe(Ansel.widgets.galleryActions.bindTo + '-toggle', 'click', function(event) {doActionToggle('" . $this->_params['bindTo'] . "', 'galleryActions'); Event.stop(event)});";

        Horde::addInlineScript($js, 'dom');
    }

    public function handle($args, $post)
    {
         if (!isset($post['value'])) {
            return 0;
         }
         $GLOBALS['prefs']->setValue('show_actions', $post['value']);

        return 1;
    }

}
