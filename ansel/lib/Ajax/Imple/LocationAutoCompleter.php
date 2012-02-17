<?php
/**
 * Autocompleter for textual location data.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_LocationAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    protected function _attach($js_params)
    {
        $js_params['indicator'] = $this->_params['triggerId'] . '_loading_img';
        $js_params['tokens'] = array();

        $ret = array(
            'params' => $js_params,
            'raw_params' => array(
                'onSelect' => 'function (v) { ' . $this->_params['map'] . '.ll = Ansel.ajax.locationAutoCompleter.geocache[v]; return v; }'
            ),
            'var' => "Ansel.ajax['locationAutoCompleter']"
        );

        /* Use ajax? */
        if (!$GLOBALS['session']->exists('ansel', 'ajax_locationac')) {
            try {
                $results = $GLOBALS['injector']->getInstance('Ansel_Storage')->searchLocations();
                $GLOBALS['session']->set('ansel', 'ajax_locationac', (count($results) > 50));
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        if ($GLOBALS['session']->get('ansel', 'ajax_locationac')) {
            $ret['ajax'] = 'LocationAutoCompleter';
        } else {
            if (empty($results)) {
                $results = $GLOBALS['injector']->getInstance('Ansel_Storage')->searchLocations();
            }
            $ret['browser'] = Horde_Serialize::serialize($results, Horde_Serialize::JSON);
        }

        return $ret;
    }

    public function handle($args, $post)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Horde_Util::getFormData($args['input']))) {
            return array();
        }
        try {
            $locs = $GLOBALS['injector']->getInstance('Ansel_Storage')->searchLocations($input);
            if (!count($locs)) {
                $locs = new StdClass();
            }
        } catch (Ansel_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            $locs = new StdClass();
        }
        return $locs;
    }

}
