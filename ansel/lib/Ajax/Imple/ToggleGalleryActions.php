<?php
/**
 * Ansel_Ajax_Imple_ToggleGalleryActions:: class for performing Ajax setting of
 * the gallery show_galleryactions user pref.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_ToggleGalleryActions extends Horde_Ajax_Imple_Base
{
    public function attach()
    {
        // Include the js
        Horde::addScriptFile('togglewidget.js');

        $url = $this->_getUrl('ToggleGalleryActions', 'ansel', array('input' => 'pref_value', 'sessionWrite' => true));

        $js = array();
        $js[] = "Ansel.widgets['galleryActions'] = {'bindTo': '" . $this->_params['bindTo'] . "', 'url': '" . $url . "'}";
        $js[] = "Event.observe(Ansel.widgets.galleryActions.bindTo + '-toggle', 'click', function(event) {doActionToggle('" . $this->_params['bindTo'] . "', 'galleryActions'); Event.stop(event)});";

        Horde::addInlineScript($js, 'dom');
    }

    public function handle($args, $post)
    {
         if (empty($args['input'])) {
            return 0;
         }
         $input = Horde_Util::getPost($args['input']);
         $GLOBALS['prefs']->setValue('show_actions', $input);

        return 1;
    }

}
