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
class Ansel_Ajax_Imple_LocationAutoCompleter extends Horde_Ajax_Imple_Base
{
    public function __construct($params)
    {
        if (!empty($params['triggerId'])) {
            if (empty($params['resultsId'])) {
                $params['resultsId'] = $params['triggerId'] . '_results';
            }
        }

        parent::__construct($params);
    }

    public function attach()
    {
        Horde::addScriptFile('prototype.js', 'horde', true);
        Horde::addScriptFile('autocomplete.js', 'horde', true);
        Horde::addScriptFile('effects.js', 'horde', true);

        $url = $this->_getUrl('LocationAutoCompleter', 'ansel', array('input' => $this->_params['triggerId']));

        /* Use ajax? */
        if (!isset($_SESSION['ansel']['ajax_locationac'])) {
            $results = $GLOBALS['ansel_storage']->searchLocations();
            if (is_a($results, 'PEAR_Error')) {
                Horde::logMessage($results, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                // @TODO: This should be a config param?
                if (count($results) > 50) {
                    $_SESSION['ansel']['ajax_locationac'] = true;
                } else {
                    $_SESSION['ansel']['ajax_locationac'] = false;
                }
            }
        }

        $params = array(
            '"' . $this->_params['triggerId'] . '"',
            '"' . $this->_params['resultsId'] . '"'
        );

        $js_params = array(
            'tokens: []',
            'indicator: "' . $this->_params['triggerId'] . '_loading_img"',
            'afterUpdateElement: function(e, v) {' . $this->_params['map'] . '.ll = Ansel.ajax.locationAutoCompleter.geocache[v.collectTextNodesIgnoreClass(\'informal\')];}',
            'afterUpdateChoices: function(c, l) {if (!c.size()) {' . $this->_params['map'] . '.ll = null;}}'
        );
        $js = array();
        if ($_SESSION['ansel']['ajax_locationac']) {
            $params[] = '"' . $url . '"';
            $params[] = '{' . implode(',', $js_params) . '}';
            $js[] = 'Ansel.ajax[\'locationAutoCompleter\'] = new Ajax.Autocompleter(' . implode(',', $params) . ');';
        } else {
            if (empty($results)) {
                $results = $GLOBALS['ansel_storage']->searchLocations();
            }
            $jsparams[] = 'ignoreCase: true';
            $params[] = Horde_Serialize::serialize($results, Horde_Serialize::JSON, Horde_Nls::getCharset());
            $params[] = '{' . implode(',', $js_params) . '}';
            $js[] = 'Ansel.ajax[\'locationAutoCompleter\'] = new Autocompleter.Local(' . implode(',', $params) . ');';
        }

        Horde::addInlineScript($js, 'dom');
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
            return array('response' => 0);
        }

        $results = $locs;

        if (count($results) == 0) {
            $results = array('response' => 0, 'message' => array());
        } else {
            $results = array('response' => count($results),
                             'message' => Horde_Serialize::serialize($results, Horde_Serialize::JSON, Horde_Nls::getCharset()));
        }

        return $results;
    }

}
