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
    public function attach()
    {
        global $page_output;

        $page_output->addScriptFile('togglewidget.js');

        $url = $this->_getUrl('ToggleOtherGalleries', 'ansel', array('post' => 'value', 'sessionWrite' => true));

        $page_output->addInlineScript(array(
            "Ansel.widgets['otherGalleries'] = {'bindTo': '" . $this->_params['bindTo'] . "', 'url': '" . $url . "'}",
            "Event.observe(Ansel.widgets.otherGalleries.bindTo + '-toggle', 'click', function(event) {doActionToggle('" . $this->_params['bindTo'] . "', 'otherGalleries'); Event.stop(event)});"
        ), true);
    }

    public function handle($args, $post)
    {
         if (!isset($post['value'])) {
            return 0;
         }

        $GLOBALS['prefs']->setValue('show_othergalleries', $post['value']);

        return $GLOBALS['prefs']->getValue('show_othergalleries');
    }

}
