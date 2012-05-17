<?php
/**
 * Imple autocompleter for textual location data.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Ajax_Imple_LocationAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     */
    protected function _getAutoCompleter()
    {
        global $injector, $session;

        $opts = array(
            'onSelect' => 'function (v) {' . $this->_params['map'] . '.ll = Ansel.ajax.locationAutoCompleter.geocache[v]; return v;}',
            'tokens' => array()
        );

        /* Use ajax? */
        if (!$session->exists('ansel', 'ajax_locationac')) {
            try {
                $results = $injector->getInstance('Ansel_Storage')->searchLocations();
                $session->set('ansel', 'ajax_locationac', (count($results) > 50));
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        if ($session->get('ansel', 'ajax_locationac')) {
            return new Horde_Core_Ajax_Imple_AutoCompleter_Ajax($opts);
        }

        if (empty($results)) {
            $results = $injector->getInstance('Ansel_Storage')->searchLocations();
        }

        return new Horde_Core_Ajax_Imple_AutoCompleter_Local($results, $opts);
    }

    /**
     */
    protected function _handleAutoCompleter($input)
    {
        $locs = array();

        try {
            $locs = $GLOBALS['injector']->getInstance('Ansel_Storage')->searchLocations($input);
        } catch (Ansel_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        return $locs;
    }

}
