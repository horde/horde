<?php
/**
 * Autocompleter for textual location data.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_LocationAutoCompleter extends Horde_Ajax_Imple_AutoCompleter
{
    protected function _attach($js_params)
    {
        $js_params['indicator'] = $this->_params['triggerId'] . '_loading_img';
        $js_params['tokens'] = array();

        $ret = array(
            'params' => $js_params,
            'raw_params' => array(
                'onSelect' => 'function (v) { ' . $this->_params['map'] . '.ll = Ansel.ajax.locationAutoCompleter.geocache[v]; }',
                'onShow' => 'function (e) { if (!e.size()) ' . $this->_params['map'] . '.ll = null; }'
            ),
            'var' => "Ansel.ajax['locationAutoCompleter']"
        );

        /* Use ajax? */
        if (!isset($_SESSION['ansel']['ajax_locationac'])) {
            $results = $GLOBALS['ansel_storage']->searchLocations();
            if ($results instanceof PEAR_Error) {
                Horde::logMessage($results, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                // @TODO: This should be a config param?
                $_SESSION['ansel']['ajax_locationac'] = (count($results) > 50);
            }
        }

        if (!empty($_SESSION['ansel']['ajax_locationac'])) {
            $ret['ajax'] = 'LocationAutoCompleter';
        } else {
            if (empty($results)) {
                $results = $GLOBALS['ansel_storage']->searchLocations();
            }
            $ret['browser'] = Horde_Serialize::serialize($results, Horde_Serialize::JSON);
        }

        return $ret;
    }

    public function handle($args)
    {
        include_once dirname(__FILE__) . '/../../base.php';

        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Horde_Util::getFormData($args['input']))) {
            return array();
        }
        $locs = $GLOBALS['ansel_storage']->searchLocations($input);
        if (is_a($locs, 'PEAR_Error')) {
            Horde::logMessage($locs->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            $locs = array();
        }

        return Horde_Serialize::serialize($locs, Horde_Serialize::JSON, Horde_Nls::getCharset());
    }

}
