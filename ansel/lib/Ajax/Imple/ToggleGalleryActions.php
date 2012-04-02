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
    public function attach()
    {
        global $page_output;

        $page_output->addScriptFile('togglewidget.js');

        $url = $this->_getUrl('ToggleGalleryActions', 'ansel', array('post' => 'value', 'sessionWrite' => true));

        $page_output->addInlineScript(array(
            "Ansel.widgets['galleryActions'] = {'bindTo': '" . $this->_params['bindTo'] . "', 'url': '" . $url . "'}",
            "Event.observe(Ansel.widgets.galleryActions.bindTo + '-toggle', 'click', function(event) {doActionToggle('" . $this->_params['bindTo'] . "', 'galleryActions'); Event.stop(event)});"
        ), true);
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
