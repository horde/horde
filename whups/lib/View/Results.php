<?php
/**
 * Whups_View for displaying a list of tickets.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Robert E. Coyle <robertcoyle@hotmail.com>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Whups
 */
class Whups_View_Results extends Whups_View_Base
{
    protected $_id;

    public function __construct($params)
    {
        parent::__construct($params);
        $this->_id = uniqid(mt_rand());
    }

    public function html()
    {
        $GLOBALS['page_output']->addScriptFile('tables.js', 'horde');

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
