<?php
/**
 * This is a view of Hermes's sidebar.
 *
 * This is for the dynamic view. For traditional the view, see
 * Hermes_Application::sidebar().
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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

        // $today = new Horde_Date($_SERVER['REQUEST_TIME']);
        // $sidebar->today = $today->format('F Y');

        // $sidebar->weekdays = array();
        // for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7;
        //      $i < $c;
        //      $i++) {
        //     $weekday = Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1)));
        //     $sidebar->weekdays[$weekday] = Horde_String::substr($weekday, 0, 2);
        // }

        // /* Calendars. */
        // $sidebar->newShares = $registry->getAuth() &&
        //     !$prefs->isLocked('default_share');
        // $sidebar->isAdmin = $registry->isAdmin();
        // $sidebar->resources = $GLOBALS['conf']['resource']['driver'] == 'sql';

        $this->content = $sidebar->render('dynamic/sidebar');
    }
}
