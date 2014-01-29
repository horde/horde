<?php
/**
 * This is a view of Hermes's sidebar.
 *
 * This is for the dynamic view. For traditional the view, see
 * Hermes_Application::sidebar().
 *
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Hermes
 */
class Hermes_View_Sidebar extends Horde_View_Sidebar
{
    /**
     * Constructor.
     *
     * @param array $config  Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        global $prefs, $registry;

        parent::__construct($config);
        $sidebar = $GLOBALS['injector']->createInstance('Horde_View');
        $this->content = $sidebar->render('dynamic/sidebar');
    }
}
