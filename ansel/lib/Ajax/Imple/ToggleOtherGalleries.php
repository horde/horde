<?php
/**
 * Ansel_Ajax_Imple_ToggleOtherGalleries:: class for performing Ajax setting of
 * the gallery show_actions user pref.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_ToggleOtherGalleries extends Horde_Ajax_Imple_Base
{
    public function attach()
    {
        // Include the js
        Horde::addScriptFile('prototype.js', 'horde');
        Horde::addScriptFile('togglewidget.js');

        $url = $this->_getUrl('ToggleOtherGalleries', 'ansel', array('input' => 'pref_value', 'sessionWrite' => true));
        $js = array();
        $js[] = "Ansel.widgets['otherGalleries'] = {'bindTo': '" . $this->_params['bindTo'] . "', 'url': '" . $url . "'}";
        $js[] = "Event.observe(Ansel.widgets.otherGalleries.bindTo + '-toggle', 'click', function(event) {doActionToggle('" . $this->_params['bindTo'] . "', 'otherGalleries'); Event.stop(event)});";

        Horde::addInlineScript($js, 'dom');
    }

    public function handle($args)
    {
         if (empty($args['input'])) {
            return 0;
         }
        $input = Horde_Util::getPost($args['input']);
        $GLOBALS['prefs']->setValue('show_othergalleries', $input);
        echo $GLOBALS['prefs']->getValue('show_othergalleries');

        return 1;
    }

}
