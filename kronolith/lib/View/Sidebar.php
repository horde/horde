<?php
/**
 * This is a view of Kronolith's sidebar.
 *
 * This is for the dynamic view. For traditional the view, see
 * Kronolith_Application::sidebar().
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_View_Sidebar extends Horde_View_Sidebar
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

        $blank = new Horde_Url();
        $this->addNewButton(
            _("_New Event"),
            $blank,
            array('id' => 'kronolithNewEvent')
        );
        $this->newExtra = $blank->link(
            array_merge(
                array('id' => 'kronolithQuickEvent'),
                Horde::getAccessKeyAndTitle(_("Quick _insert"), false, true)
            )
        );

        $sidebar = $GLOBALS['injector']->createInstance('Horde_View');

        /* Minical. */
        $today = new Horde_Date($_SERVER['REQUEST_TIME']);
        $sidebar->today = $today->format('F Y');

        $sidebar->weekdays = array();
        for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7;
             $i < $c;
             $i++) {
            $weekday = Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1)));
            $sidebar->weekdays[$weekday] = Horde_String::substr($weekday, 0, 2);
        }

        /* Calendars. */
        $sidebar->newShares = $registry->getAuth() &&
            !$prefs->isLocked('default_share');
        $sidebar->isAdmin = $registry->isAdmin();
        $sidebar->resources = $GLOBALS['conf']['resource']['driver'] == 'sql';

        $this->content = $sidebar->render('dynamic/sidebar');
    }
}
