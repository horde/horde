<?php
/**
 * Whups_View for displaying a list of tickets.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * @author  Robert E. Coyle <robertcoyle@hotmail.com>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Whups
 */
class Whups_View_Results extends Whups_View {

    var $_id;

    function Whups_View_Results($params)
    {
        parent::Whups_View($params);
        $this->_id = uniqid(mt_rand());
    }

    function html()
    {
        Horde::addScriptFile('tables.js', 'horde', true);

        global $prefs, $registry, $session;

        $sortby = $prefs->getValue('sortby');
        $sortdir = $prefs->getValue('sortdir');
        $sortdirclass = $sortdir ? 'sortup' : 'sortdown';

        $ids = array();
        foreach ($this->_params['results'] as $info) {
            $ids[] = $info['id'];
        }
        $session->set('whups', 'tickets', $ids);

        include WHUPS_TEMPLATES . '/view/results.inc';
    }

}
