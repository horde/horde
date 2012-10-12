<?php
/**
 * Kronolith application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Kronolith through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kronolith
 */

/* Determine the base directories. */
if (!defined('KRONOLITH_BASE')) {
    define('KRONOLITH_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(KRONOLITH_BASE . '/config/horde.local.php')) {
        include KRONOLITH_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', KRONOLITH_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Kronolith_Application extends Horde_Registry_Application
{
    /**
     */
    public $features = array(
        'alarmHandler' => true,
        'dynamicView' => true,
        'smartmobileView' => true
    );

    /**
     */
    public $version = 'H5 (4.0.0-git)';

    /**
     * Global variables defined:
     * - $kronolith_shares: TODO
     */
    protected function _init()
    {
        /* For now, autoloading the Content_* classes depend on there being a
         * registry entry for the 'content' application that contains at least
         * the fileroot entry. */
        $GLOBALS['injector']->getInstance('Horde_Autoloader')
            ->addClassPathMapper(
                new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));

        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception(_("The Content_Tagger class could not be found. Make sure the Content application is installed."));
        }

        $GLOBALS['injector']->bindFactory('Kronolith_Geo', 'Kronolith_Factory_Geo', 'create');
        $GLOBALS['injector']->bindFactory('Kronolith_Shares', 'Kronolith_Factory_Shares', 'create');

        if (!$GLOBALS['prefs']->getValue('dynamic_view')) {
            $this->features['dynamicView'] = false;
        }
        if ($GLOBALS['registry']->getView() != Horde_Registry::VIEW_DYNAMIC ||
            !$GLOBALS['prefs']->getValue('dynamic_view') ||
            empty($this->initParams['nodynamicinit'])) {
            Kronolith::initialize();
        }
    }

    protected function _authenticated()
    {
        /* Set the timezone variable, if available. */
        $GLOBALS['registry']->setTimeZone();

        /* Store the request timestamp if it's not already present. */
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }
    }

    /**
     */
    public function perms()
    {
        return array(
            'max_events' => array(
                'title' => _("Maximum Number of Events"),
                'type' => 'int'
            )
        );
    }

    /**
     */
    public function menu($menu)
    {
        global $browser, $conf, $notification, $page_output, $registry, $session;

        /* Check here for guest calendars so that we don't get multiple
         * messages after redirects, etc. */
        if (!$registry->getAuth() && !count(Kronolith::listCalendars())) {
            $notification->push(_("No calendars are available to guests."));
        }

        if ($browser->hasFeature('dom')) {
            Horde_Core_Ui_JsCalendar::init(array(
                'click_month' => true,
                'click_week' => true,
                'click_year' => true,
                'full_weekdays' => true
            ));
            $page_output->addScriptFile('goto.js');
            $page_output->addInlineJsVars(array(
                'KronolithGoto.dayurl' => strval(Horde::url('day.php')),
                'KronolithGoto.monthurl' => strval(Horde::url('month.php')),
                'KronolithGoto.weekurl' => strval(Horde::url('week.php')),
                'KronolithGoto.yearurl' => strval(Horde::url('year.php'))
            ));
            $menu->add(new Horde_Url(''), _("_Goto"), 'kronolith-icon-goto', null, '', null, 'kgotomenu');
        }
        $menu->add(Horde::url('search.php'), _("_Search"), 'kronolith-icon-search');

        /* Import/Export. */
        if ($conf['menu']['import_export'] &&
            !Kronolith::showAjaxView()) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'horde-data');
        }

        if (strlen($session->get('kronolith', 'display_cal'))) {
            $menu->add(Horde::selfUrl(true)->add('display_cal', ''),
                       $registry->getAuth()
                           ? _("Return to my calendars")
                           : _("Return to calendars"),
                       'kronolith-icon-back',
                       null, null, null, '__noselection');
        }
    }

    /**
     * Adds additional items to the sidebar.
     *
     * This is for the traditional view. For the dynamic view, see
     * Kronolith_View_Sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
        if (Kronolith::getDefaultCalendar(Horde_Perms::EDIT) &&
            ($perms->hasAppPermission('max_events') === true ||
             $perms->hasAppPermission('max_events') > Kronolith::countEvents())) {
            $sidebar->addNewButton(_("_New Event"), Horde::url('new.php')->add('url', Horde::selfUrl(true, false, true)));
        }

        if (strlen($GLOBALS['session']->get('kronolith', 'display_cal'))) {
            $calendars = Kronolith::displayedCalendars();
            $sidebar->containers['calendars'] = array(
                'header' => array(
                    'id' => 'kronolith-toggle-calendars',
                    'label' => ngettext("Showing calendar:", "Showing calendars:", count($calendars)),
                    'collapsed' => false,
                ),
            );
            foreach ($calendars as $calendar) {
                $row = array(
                    'label' => $calendar->name(),
                    'color' => $calendar->background(),
                    'type' => 'checkbox',
                );
                $sidebar->addRow($row, 'calendars');
            }
            return;
        }

        $user = $GLOBALS['registry']->getAuth();
        $url = Horde::selfUrl();
        $edit = Horde::url('calendars/edit.php');

        $sidebar->containers['my'] = array(
            'header' => array(
                'id' => 'kronolith-toggle-my',
                'label' => _("My Calendars"),
                'collapsed' => false,
            ),
        );
        if (!$GLOBALS['prefs']->isLocked('default_share')) {
            $sidebar->containers['my']['header']['add'] = array(
                'url' => Horde::url('calendars/create.php'),
                'label' => _("Create a new Local Calendar"),
            );
        }
        if ($GLOBALS['registry']->isAdmin()) {
            $sidebar->containers['system'] = array(
                'header' => array(
                    'id' => 'kronolith-toggle-system',
                    'label' => _("System Calendars"),
                    'collapsed' => true,
                ),
            );
            $sidebar->containers['system']['header']['add'] = array(
                'url' => Horde::url('calendars/create.php')->add('system', 1),
                'label' => _("Create a new System Calendar"),
            );
        }
        $sidebar->containers['shared'] = array(
            'header' => array(
                'id' => 'kronolith-toggle-shared',
                'label' => _("Shared Calendars"),
                'collapsed' => true,
            ),
        );
        foreach (Kronolith::listInternalCalendars() as $id => $calendar) {
            $row = array(
                'selected' => in_array($id, $GLOBALS['display_calendars']),
                'url' => $url->copy()->add('toggle_calendar', $id),
                'label' => $calendar->get('name'),
                'color' => Kronolith::backgroundColor($calendar),
                'edit' => $edit->add('c', $calendar->getName()),
                'type' => 'checkbox',
            );
            if ($calendar->get('owner') && $calendar->get('owner') == $user) {
                $sidebar->addRow($row, 'my');
            } else {
                if ($calendar->get('owner')) {
                    $row['label'] .= ' [' . $GLOBALS['registry']->convertUsername($calendar->get('owner'), false) . ']';
                }
                $sidebar->addRow($row, 'shared');
            }
        }

        if ($GLOBALS['registry']->isAdmin()) {
            foreach ($GLOBALS['injector']->getInstance('Kronolith_Shares')->listSystemShares() as $id => $calendar) {
                $row = array(
                    'selected' => in_array($id, $GLOBALS['display_calendars']),
                    'url' => $url->copy()->add('toggle_calendar', $id),
                    'label' => $calendar->get('name'),
                    'color' => Kronolith::backgroundColor($calendar),
                    'edit' => $edit->add('c', $calendar->getName()),
                    'type' => 'checkbox',
                );
                $sidebar->addRow($row, 'system');
            }

            if (!empty($GLOBALS['conf']['resource']['driver'])) {
                $sidebar->containers['groups'] = array(
                    'header' => array(
                        'id' => 'kronolith-toggle-groups',
                        'label' => _("Resource Groups"),
                        'collapsed' => true,
                        'add' => array(
                            'url' => Horde::url('resources/groups/create.php'),
                            'label' => _("Create a new Resource Group"),
                        ),
                    ),
                );
                $editGroups = Horde::url('resources/groups/edit.php');
                $sidebar->containers['resources'] = array(
                    'header' => array(
                        'id' => 'kronolith-toggle-resources',
                        'label' => _("Resources"),
                        'collapsed' => true,
                        'add' => array(
                            'url' => Horde::url('resources/create.php'),
                            'label' => _("Create a new Resource"),
                        ),
                    ),
                );
                $edit = Horde::url('resources/edit.php');
                foreach (Kronolith::getDriver('Resource')->listResources() as $resource) {
                    if ($resource->get('type') == Kronolith_Resource::TYPE_GROUP) {
                        $row = array(
                            'label' => $resource->get('name'),
                            'color' => '#dddddd',
                            'edit' => $editGroups->add('c', $resource->getId()),
                            'type' => 'radiobox',
                        );
                        $sidebar->addRow($row, 'groups');
                    } else {
                        $calendar = new Kronolith_Calendar_Resource(array(
                            'resource' => $resource
                        ));
                        $row = array(
                            'selected' => in_array($resource->get('calendar'), $GLOBALS['display_resource_calendars']),
                            'url' => $url->copy()->add('toggle_calendar', 'resource_' . $resource->get('calendar')),
                            'label' => $calendar->name(),
                            'color' => $calendar->background(),
                            'edit' => $edit->add('c', $resource->getId()),
                            'type' => 'checkbox',
                        );
                        $sidebar->addRow($row, 'resources');
                    }
                }
            }
        }

        foreach ($GLOBALS['all_external_calendars'] as $id => $calendar) {
            if (!$calendar->display()) {
                continue;
            }
            $app = $GLOBALS['registry']->get(
                'name',
                $GLOBALS['registry']->hasInterface($calendar->api()));
            if (!strlen($app)) {
                $app = _("Other events");
            }
            $container = 'external_' . $app;
            if (!isset($sidebar->containers[$container])) {
                $sidebar->containers[$container] = array(
                    'header' => array(
                        'id' => 'kronolith-toggle-external-' . $calendar->api(),
                        'label' => $app,
                        'collapsed' => true,
                    ),
                );
            }
            $row = array(
                'selected' => in_array($id, $GLOBALS['display_external_calendars']),
                'url' => $url->copy()->add('toggle_calendar', 'external_' . $id),
                'label' => $calendar->name(),
                'color' => $calendar->background(),
                'type' => 'checkbox',
            );
            $sidebar->addRow($row, $container);
        }

        $sidebar->containers['remote'] = array(
            'header' => array(
                'id' => 'kronolith-toggle-remote',
                'label' => _("Remote Calendars"),
                'collapsed' => true,
                'add' => array(
                    'url' => Horde::url('calendars/remote_subscribe.php'),
                    'label' => _("Subscribe to a Remote Calendar"),
                ),
            ),
        );
        $edit = Horde::url('calendars/remote_edit.php');
        foreach ($GLOBALS['all_remote_calendars'] as $id => $calendar) {
            $row = array(
                'selected' => in_array($calendar->url(), $GLOBALS['display_remote_calendars']),
                'url' => $url->copy()->add('toggle_calendar', 'remote_' . $calendar->url()),
                'label' => $calendar->name(),
                'color' => $calendar->background(),
                'edit' => $edit->add('url', $calendar->url()),
                'type' => 'checkbox',
            );
            $sidebar->addRow($row, 'remote');
        }

        if (!empty($GLOBALS['conf']['holidays']['enable'])) {
            $sidebar->containers['holidays'] = array(
                'header' => array(
                    'id' => 'kronolith-toggle-holidays',
                    'label' => _("Holidays"),
                    'collapsed' => true,
                ),
            );
            foreach ($GLOBALS['all_holidays'] as $id => $calendar) {
                $row = array(
                    'selected' => in_array($id, $GLOBALS['display_holidays']),
                    'url' => $url->copy()->add('toggle_calendar', 'holiday_' . $id),
                    'label' => $calendar->name(),
                    'color' => $calendar->background(),
                    'type' => 'checkbox',
                );
                $sidebar->addRow($row, 'holidays');
            }
        }
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_events':
                $allowed = max($allowed);
                break;
            }
        }
        return $allowed;
    }

    /**
     */
    public function removeUserData($user)
    {
        $error = false;

        // Remove all events owned by the user in all calendars.
        Kronolith::removeUserEvents($user);

        // Get the shares owned by the user being deleted.
        try {
            $kronolith_shares = $GLOBALS['injector']->getInstance('Kronolith_Shares');
            $shares = $kronolith_shares->listShares(
                $user,
                array('attributes' => $user));
            foreach ($shares as $share) {
                $kronolith_shares->removeShare($share);
            }
        } catch (Exception $e) {
            Horde::logMessage($e, 'NOTICE');
            $error = true;
        }

        /* Get a list of all shares this user has perms to and remove the
         * perms */
        try {
            $shares = $kronolith_shares->listShares($user);
            foreach ($shares as $share) {
                $share->removeUser($user);
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'NOTICE');
            $error = true;
        }

        if ($error) {
            throw new Kronolith_Exception(sprintf(_("There was an error removing calendars for %s. Details have been logged."), $user));
        }
    }

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        switch ($params['id']) {
        case 'menu':
            $menus = array(
                array('new', _("New Event"), 'new.png', Horde::url('new.php')),
                array('day', _("Day"), 'dayview.png', Horde::url('day.php')),
                array('work', _("Work Week"), 'workweekview.png', Horde::url('workweek.php')),
                array('week', _("Week"), 'weekview.png', Horde::url('week.php')),
                array('month', _("Month"), 'monthview.png', Horde::url('month.php')),
                array('year', _("Year"), 'yearview.png', Horde::url('year.php')),
                array('search', _("Search"), 'search.png', Horde::url('search.php'))
            );

            foreach ($menus as $menu) {
                $tree->addNode(array(
                    'id' => $parent . $menu[0],
                    'parent' => $parent,
                    'label' => $menu[1],
                    'expanded' => false,
                    'params' => array(
                        'icon' => Horde_Themes::img($menu[2]),
                        'url' => $menu[3]
                    )
                ));
            }
            break;
        }
    }

    /* Alarm method. */

    /**
     */
    public function listAlarms($time, $user = null)
    {
        $current_user = $GLOBALS['registry']->getAuth();
        if ((empty($user) || $user != $current_user) && !$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception_PermissionDenied();
        }

        $group = $GLOBALS['injector']->getInstance('Horde_Group');
        $kronolith_shares = $GLOBALS['injector']->getInstance('Kronolith_Shares');

        $alarm_list = array();
        $time = new Horde_Date($time);
        $calendars = is_null($user)
            ? array_keys($kronolith_shares->listAllShares())
            : $GLOBALS['display_calendars'];
        $alarms = Kronolith::listAlarms($time, $calendars, true);
        foreach ($alarms as $calendar => $cal_alarms) {
            if (!$cal_alarms) {
                continue;
            }
            try {
                $share = $kronolith_shares->getShare($calendar);
            } catch (Exception $e) {
                continue;
            }
            if (empty($user)) {
                $users = $share->listUsers(Horde_Perms::READ);
                $groups = $share->listGroups(Horde_Perms::READ);
                foreach ($groups as $gid) {
                    try {
                        $users = array_merge($users, $group->listUsers($gid));
                    } catch (Horde_Group_Exception $e) {}
                }
                $users = array_unique($users);
            } else {
                $users = array($user);
            }
            $owner = $share->get('owner');
            foreach ($cal_alarms as $event) {
                foreach ($users as $alarm_user) {
                    if ($alarm_user == $current_user) {
                        $prefs = $GLOBALS['prefs'];
                    } else {
                        $prefs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('kronolith', array(
                            'cache' => false,
                            'user' => $alarm_user
                        ));
                    }
                    $shown_calendars = unserialize($prefs->getValue('display_cals'));
                    $reminder = $prefs->getValue('event_reminder');
                    if (($reminder == 'owner' && $alarm_user == $owner) ||
                        ($reminder == 'show' && in_array($calendar, $shown_calendars)) ||
                        $reminder == 'read') {
                            $GLOBALS['registry']->setLanguageEnvironment($prefs->getValue('language'));
                            $alarm = $event->toAlarm($time, $alarm_user, $prefs);
                            if ($alarm) {
                                $alarm_list[] = $alarm;
                            }
                    }
                }
            }
        }

        return $alarm_list;
    }

    /* Download data. */

    /**
     * @throws Kronolith_Exception
     */
    public function download(Horde_Variables $vars)
    {
        global $display_calendars, $injector;

        switch ($vars->actionID) {
        case 'export':
            if ($vars->all_events) {
                $end = $start = null;
            } else {
                $start = new Horde_Date(
                    $vars->start_year,
                    $vars->start_month,
                    $vars->start_day
                );
                $start = new Horde_Date(
                    $vars->end_year,
                    $vars->end_month,
                    $vars->end_day
                );
            }

            $calendars = $vars->get('exportCal', $display_calendars);
            if (!is_array($calendars)) {
                $calendars = array($calendars);
            }
            $events = array();

            foreach ($calendars as $calendar) {
                list($type, $cal) = explode('_', $calendar, 2);
                $kronolith_driver = Kronolith::getDriver($type, $cal);
                $events[$calendar] = $kronolith_driver->listEvents(
                    $start,
                    $end,
                    array(
                        'cover_dates' => false,
                        'hide_exceptions' => ($vars->exportID == Horde_Data::EXPORT_ICALENDAR)
                    )
                );
            }

            if (empty($events)) {
                throw new Kronolith_Exception(_("There were no events to export."));
            }

            switch ($vars->exportID) {
            case Horde_Data::EXPORT_CSV:
                $data = array();
                foreach ($events as $calevents) {
                    foreach ($calevents as $dayevents) {
                        foreach ($dayevents as $event) {
                            $row = array(
                                'alarm' => $event->alarm,
                                'description' => $event->description,
                                'end_date' => sprintf('%d-%02d-%02d', $event->end->year, $event->end->month, $event->end->mday),
                                'end_time' => sprintf('%02d:%02d:%02d', $event->end->hour, $event->end->min, $event->end->sec),
                                'location' => $event->location,
                                'private' => intval($event->private),
                                'recur_type' => null,
                                'recur_end_date' => null,
                                'recur_interval' => null,
                                'recur_data' => null,
                                'start_date' => sprintf('%d-%02d-%02d', $event->start->year, $event->start->month, $event->start->mday),
                                'start_time' => sprintf('%02d:%02d:%02d', $event->start->hour, $event->start->min, $event->start->sec),
                                'tags' => implode(', ', $event->tags),
                                'title' => $event->getTitle()
                            );

                            if ($event->recurs()) {
                                $row['recur_type'] = $event->recurrence->getRecurType();
                                $row['recur_end_date'] = sprintf(
                                    '%d-%02d-%02d',
                                    $event->recurrence->recurEnd->year,
                                    $event->recurrence->recurEnd->month,
                                    $event->recurrence->recurEnd->mday
                                );
                                $row['recur_interval'] = $event->recurrence->getRecurInterval();
                                $row['recur_data'] = $event->recurrence->recurData;
                            }

                            $data[] = $row;
                        }
                    }
                }

                $injector->getInstance('Horde_Core_Factory_Data')->create('Csv', array('cleanup' => array($this, 'cleanupData')))->exportFile(_("events.csv"), $data, true);
                exit;

            case Horde_Data::EXPORT_ICALENDAR:
                $calNames = $calIds = array();
                $iCal = new Horde_Icalendar();

                foreach ($events as $calevents) {
                    foreach ($calevents as $dayevents) {
                        foreach ($dayevents as $event) {
                            $calIds[$event->calendar] = true;
                            $iCal->addComponent($event->toiCalendar($iCal));
                        }
                    }
                }

                $kshares = $injector->getInstance('Kronolith_Shares');
                foreach (array_keys($calIds) as $calId) {
                    $calNames[] = $kshares->getShare($calId)->get('name');
                }

                $iCal->setAttribute('X-WR-CALNAME', implode(', ', $calNames));

                return array(
                    'data' => $iCal->exportvCalendar(),
                    'name' => _("events.ics"),
                    'type' => 'text/calendar'
                );
            }
        }
    }

    /**
     */
    public function cleanupData()
    {
        $GLOBALS['import_step'] = 1;
        return Horde_Data::IMPORT_FILE;
    }

}
