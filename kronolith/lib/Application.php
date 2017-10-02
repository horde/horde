<?php
/**
 * Kronolith application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Kronolith through this API.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kronolith
 */

use Horde\Backup;
use Sabre\CalDAV;

/* Determine the base directories. */
if (!defined('KRONOLITH_BASE')) {
    define('KRONOLITH_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(KRONOLITH_BASE . '/config/horde.local.php')) {
        include KRONOLITH_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(KRONOLITH_BASE . '/..'));
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
        'smartmobileView' => true,
        'modseq' => true
    );

    /**
     */
    public $version = 'H5 (5.0.0-git)';

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

        /* Set the timezone variable, if available. */
        $GLOBALS['registry']->setTimeZone();

        /* Store the request timestamp if it's not already present. */
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        if (!$GLOBALS['prefs']->getValue('dynamic_view')) {
            $this->features['dynamicView'] = false;
        }
        if ($GLOBALS['registry']->getView() != Horde_Registry::VIEW_DYNAMIC ||
            !$GLOBALS['prefs']->getValue('dynamic_view') ||
            empty($this->initParams['nodynamicinit'])) {
            Kronolith::initialize();
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
            ),
            'resource_management' => array(
                'title' => _("Resource Management"),
                'type' => 'boolean')
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
        global $calendar_manager, $conf, $injector, $prefs, $registry, $session;

        $admin = $registry->isAdmin();
        $perms = $injector->getInstance('Horde_Core_Perms');

        if (Kronolith::getDefaultCalendar(Horde_Perms::EDIT) &&
            ($perms->hasAppPermission('max_events') === true ||
             $perms->hasAppPermission('max_events') > Kronolith::countEvents())) {
            $sidebar->addNewButton(_("_New Event"), Horde::url('new.php')->add('url', Horde::signUrl(Horde::selfUrl(true, false, true))));
        }

        if (strlen($session->get('kronolith', 'display_cal'))) {
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

        $user = $registry->getAuth();
        $url = Horde::selfUrl();
        $edit = Horde::url('calendars/edit.php');

        $sidebar->containers['my'] = array(
            'header' => array(
                'id' => 'kronolith-toggle-my',
                'label' => _("My Calendars"),
                'collapsed' => false,
            ),
        );
        if (!$prefs->isLocked('default_share')) {
            $sidebar->containers['my']['header']['add'] = array(
                'url' => Horde::url('calendars/create.php'),
                'label' => _("Create a new Local Calendar"),
            );
        }
        if ($admin) {
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
            $owner = $calendar->get('owner');
            if ($admin && empty($owner)) {
                continue;
            }
            $row = array(
                'selected' => in_array($id, $calendar_manager->get(Kronolith::DISPLAY_CALENDARS)),
                'url' => $url->copy()->add('toggle_calendar', $id),
                'label' => Kronolith::getLabel($calendar),
                'color' => Kronolith::backgroundColor($calendar),
                'edit' => $edit->add('c', $calendar->getName()),
                'type' => 'checkbox',
            );
            if ($calendar->get('owner') && $calendar->get('owner') == $user) {
                $sidebar->addRow($row, 'my');
            } else {
                $sidebar->addRow($row, 'shared');
            }
        }

        if ($admin) {
            foreach ($injector->getInstance('Kronolith_Shares')->listSystemShares() as $id => $calendar) {
                $row = array(
                    'selected' => in_array($id, $calendar_manager->get(Kronolith::DISPLAY_CALENDARS)),
                    'url' => $url->copy()->add('toggle_calendar', $id),
                    'label' => $calendar->get('name'),
                    'color' => Kronolith::backgroundColor($calendar),
                    'edit' => $edit->add('c', $calendar->getName()),
                    'type' => 'checkbox',
                );
                $sidebar->addRow($row, 'system');
            }
        }
        if (!empty($conf['resources']['enabled']) &&
            ($admin || $perms->hasAppPermission('resource_management'))) {

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
                if ($resource->get('isgroup')) {
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
                        'selected' => in_array($resource->get('calendar'), $calendar_manager->get(Kronolith::DISPLAY_RESOURCE_CALENDARS)),
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

        foreach ($calendar_manager->get(Kronolith::ALL_EXTERNAL_CALENDARS) as $id => $calendar) {
            if (!$calendar->display()) {
                continue;
            }
            $app = $registry->get(
                'name',
                $registry->hasInterface($calendar->api()));
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
                'selected' => in_array($id, $calendar_manager->get(Kronolith::DISPLAY_EXTERNAL_CALENDARS)),
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
        foreach ($calendar_manager->get(Kronolith::ALL_REMOTE_CALENDARS) as $calendar) {
            $row = array(
                'selected' => in_array($calendar->url(), $calendar_manager->get(Kronolith::DISPLAY_REMOTE_CALENDARS)),
                'url' => $url->copy()->add('toggle_calendar', 'remote_' . $calendar->url()),
                'label' => $calendar->name(),
                'color' => $calendar->background(),
                'edit' => $edit->add('url', $calendar->url()),
                'type' => 'checkbox',
            );
            $sidebar->addRow($row, 'remote');
        }

        if (!empty($conf['holidays']['enable'])) {
            $sidebar->containers['holidays'] = array(
                'header' => array(
                    'id' => 'kronolith-toggle-holidays',
                    'label' => _("Holidays"),
                    'collapsed' => true,
                ),
            );
            foreach ($calendar_manager->get(Kronolith::ALL_HOLIDAYS) as $id => $calendar) {
                $row = array(
                    'selected' => in_array($id, $calendar_manager->get(Kronolith::DISPLAY_HOLIDAYS)),
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
        try {
            Kronolith::removeUserEvents($user);
        } catch (Exception $e) {
            Horde::log($e, 'NOTICE');
            $error = true;
        }

        // Get the shares owned by the user being deleted.
        try {
            $kronolith_shares = $GLOBALS['injector']->getInstance('Kronolith_Shares');
            $shares = $kronolith_shares->listShares(
                $user,
                array('attributes' => $user)
            );
            foreach ($shares as $share) {
                $kronolith_shares->removeShare($share);
            }
        } catch (Exception $e) {
            Horde::log($e, 'NOTICE');
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
            Horde::log($e, 'NOTICE');
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
                array('year', _("Year"), 'yearview.png', Horde::url('year.php'))
            );
            if (Kronolith::showAjaxView()) {
                if (Kronolith::hasApiPermission('tasks')) {
                    $menus[] = array('tasks', _("Tasks"), 'tasks.png', $GLOBALS['registry']->get('webroot') . '#tasks');
                }
                $menus[] = array('agenda', _("Agenda"), 'agenda.png', $GLOBALS['registry']->get('webroot') . '#agenda');
            }
            // Dynamic view has no dedicated search page.
            if (!Kronolith::showAjaxView()) {
                $menus[] = array('search', _("Search"), 'search.png', Horde::url('search.php'));
            }
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
            : $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_CALENDARS);
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
                    // Don't show alarms for private events if not the owner.
                    if ($event->isPrivate($alarm_user)) {
                        continue;
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

    /* Backup/restore */

    /**
     */
    public function backup(array $users = array())
    {
        global $injector;

        $factory = $injector->getInstance('Kronolith_Factory_Driver');
        $kronolith_shares = $injector->getInstance('Kronolith_Shares');

        if (!$users) {
            foreach ($kronolith_shares->listAllShares() as $share) {
                $users[$share->get('owner')] = true;
            }
            $users = array_keys($users);
        }

        $getUser = function($user) use ($factory, $kronolith_shares)
        {
            global $registry;

            $backup = new Backup\User($user);
            $this->_backupPrefs($backup, 'kronolith');

            $shares = $kronolith_shares->listShares(
                $user, array('perm' => Horde_Perms::EDIT)
            );
            if (!$shares) {
                return $backup;
            }

            // Need to pushApp() here because this method is called delayed,
            // but we need Kronolith's $conf.
            $pushed = $registry->pushApp(
                'kronolith', array('check_perms' => false)
            );
            $calendars = array();
            foreach ($shares as $share) {
                if ($share->get('owner') == $user) {
                    $calendars[$share->getId()] = $share->toHash();
                }
                $backup->collections[] = new Backup\Collection(
                    new Kronolith\Backup\Events(
                        Kronolith::getDriver(),
                        $share->getName(),
                        $user
                    ),
                    $user,
                    'events'
                );
            }
            $backup->collections[] = new Backup\Collection(
                new ArrayIterator($calendars),
                $user,
                'calendars'
            );
            if ($pushed === true) {
                $registry->popApp();
            }

            return $backup;
        };

        return new Backup\Users(new ArrayIterator($users), $getUser);
    }

    /**
     */
    public function restore(Backup\Collection $data)
    {
        global $injector;

        $count = 0;
        switch ($data->getType()) {
        case 'preferences':
            $count = $this->_restorePrefs($data, 'kronolith');
            break;

        case 'calendars':
            $kronolith_shares = $injector->getInstance('Kronolith_Shares');
            foreach ($data as $calendar) {
                $calendar['owner'] = $data->getUser();
                $calendar['attributes'] = array_intersect_key(
                    $calendar['attributes'],
                    array(
                        'name'          => true,
                        'color'         => true,
                        'desc'          => true,
                        'calendar_type' => true,
                    )
                );
                $kronolith_shares->fromHash($calendar);
                $count++;
            }
            break;

        case 'events':
            $factory = $injector->getInstance('Kronolith_Factory_Driver');
            $map = array();
            foreach ($data as $event) {
                $driver = Kronolith::getDriver(null, $event['calendar']);
                $object = $driver->getEvent();
                $object->fromHash($event);
                if (!empty($event['attendees'])) {
                    $object->attendees = new Kronolith_Attendee_List();
                    foreach ($event['attendees'] as $attendee) {
                        $object->attendees->add(
                            new Kronolith_Attendee($attendee)
                        );
                    }
                }
                if (!empty($event['original_date'])) {
                    $object->exceptionoriginaldate = new Horde_Date(
                        $event['original_date']
                    );
                }
                if (!empty($event['recurrence'])) {
                    $object->recurrence = Horde_Date_Recurrence::fromHash(
                        $event['recurrence']
                    );
                }
                if (!empty($event['resources'])) {
                    foreach ($event['resources'] as $resource) {
                        try {
                            $object->addResource(
                                Kronolith_Resource::getResource(
                                    $resource['calendar']
                                ),
                                $resource['response']
                            );
                        } catch (Horde_Exception_NotFound $e) {
                        }
                    }
                }
                $object->baseid      = $event['baseid'];
                $object->creator     = $event['creator'];
                $object->geoLocation = $event['geo_location'];
                $object->methods     = $event['methods'];
                $object->status      = $event['status'];
                $object->url         = $event['url'];

                $driver->saveEvent($object);

                if (!empty($event['files'])) {
                    foreach ($event['files'] as $file) {
                        $file['data'] = base64_decode($file['data']);
                        $object->addFileFromData($file);
                    }
                }

                $count++;
            }
            break;
        }

        return $count;
    }

    /**
     */
    public function restoreDependencies()
    {
        return array('events' => array('calendars'));
    }

    /* Download data. */

    /**
     * @throws Kronolith_Exception
     */
    public function download(Horde_Variables $vars)
    {
        global $display_calendars, $injector;

        switch ($vars->actionID) {
        case 'download_file':
            $source = Horde_Util::getFormData('source');
            $key = Horde_Util::getFormData('key');
            $filename = Horde_Util::getFormData('file');
            $type = Horde_Util::getFormData('type');

            list($driver_type, $calendar) = explode('|', $source);
            if ($driver_type == 'internal' &&
                !Kronolith::hasPermission($calendar, Horde_Perms::SHOW)) {
                $GLOBALS['notification']->push(_("Permission Denied"), 'horde.error');
                return false;
            }

            try {
                $driver = Kronolith::getDriver($driver_type, $calendar);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return false;
            }
            $event = $driver->getEvent($key);

            /* Check permissions. */
            if (!$event->hasPermission(Horde_Perms::READ)) {
                throw new Kronolith_Exception(_("You do not have permission to view this event."));
            }

            try {
                $data = $event->vfsInit()->read(Kronolith::VFS_PATH . '/' . $event->getVfsUid(), $filename);
            } catch (Horde_Vfs_Exception $e) {
                Horde::log($e, 'ERR');
                throw new Kronolith_Exception(sprintf(_("Access denied to %s"), $filename));
            }

            try {
                return array(
                    'data' => $data,
                    'name' => $vars->file,
                    'type' => $type
                );
            } catch (Horde_Vfs_Exception $e) {
                Horde::log($e, 'ERR');
                throw new Kronolith_Exception(sprintf(_("Access denied to %s"), $vars->file));
            }

        case 'export':
            if ($vars->all_events) {
                $end = $start = null;
            } else {
                $start = new Horde_Date(
                    $vars->start_year,
                    $vars->start_month,
                    $vars->start_day
                );
                $end = new Horde_Date(
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
                $calendarObject = Kronolith::getCalendar($kronolith_driver);
                if (!$calendarObject ||
                    !$calendarObject->hasPermission(Horde_Perms::READ)) {
                    throw new Horde_Exception_PermissionDenied();
                }
                $events[$calendar] = $kronolith_driver->listEvents(
                    $start,
                    $end,
                    array(
                        'cover_dates' => false,
                        'hide_exceptions' => ($vars->exportID == Horde_Data::EXPORT_ICALENDAR)
                    )
                );
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
                                'end_date' => $event->end->format('Y-m-d'),
                                'end_time' => $event->end->format('H:i:s'),
                                'location' => $event->location,
                                'private' => intval($event->private),
                                'recur_type' => null,
                                'recur_end_date' => null,
                                'recur_interval' => null,
                                'recur_data' => null,
                                'start_date' => $event->start->format('Y-m-d'),
                                'start_time' => $event->start->format('H:i:s'),
                                'tags' => implode(', ', $event->tags),
                                'title' => $event->getTitle()
                            );

                            if ($event->recurs()) {
                                $row['recur_type'] = $event->recurrence->getRecurType();
                                if ($event->recurrence->hasRecurEnd()) {
                                    $row['recur_end_date'] = $event->recurrence->recurEnd->format('Y-m-d');
                                }
                                $row['recur_interval'] = $event->recurrence->getRecurInterval();
                                $row['recur_data'] = $event->recurrence->recurData;
                            }

                            $data[] = $row;
                        }
                    }
                }

                $injector->getInstance('Horde_Core_Factory_Data')
                    ->create('Csv', array('cleanup' => array($this, 'cleanupData')))
                    ->exportFile(_("events.csv"), $data, true);
                exit;

            case Horde_Data::EXPORT_ICALENDAR:
                $calNames = array();
                $iCal = new Horde_Icalendar();

                foreach ($events as $calevents) {
                    foreach ($calevents as $dayevents) {
                        foreach ($dayevents as $event) {
                            $calNames[Kronolith::getCalendar($event->getDriver())->name()] = true;
                            $iCal->addComponent($event->toiCalendar($iCal));
                        }
                    }
                }

                $iCal->setAttribute('X-WR-CALNAME', implode(', ', array_keys($calNames)));

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

    /* DAV methods. */

    /**
     */
    public function davGetCollections($user)
    {
        global $calendar_manager, $injector, $registry;

        $hordeUser = $user;
        try {
            $hordeUser = $injector->getInstance('Horde_Core_Hooks')
                ->callHook('davusername', 'horde', array($hordeUser, true));
        } catch (Horde_Exception_HookNotSet $e) {
        }
        $hordeUser = $registry->convertUsername($hordeUser, true);
        $shares = $injector->getInstance('Kronolith_Shares')
            ->listShares($hordeUser);
        $dav = $injector->getInstance('Horde_Dav_Storage');
        $calendars = array();
        foreach ($shares as $id => $share) {
            if ($user == '-system-' && $share->get('owner')) {
                continue;
            }
            $calendar = $calendar_manager
                ->getEntry(Kronolith::ALL_CALENDARS, $id)
                ->toHash();
            try {
                $id = $dav->getExternalCollectionId($id, 'calendar');
            } catch (Horde_Dav_Exception $e) {
            }
            $calendars[] = array(
                'id' => $id,
                'uri' => $id,
                '{' . CalDAV\Plugin::NS_CALENDARSERVER . '}shared-url' =>
                    $calendar['caldav'],
                'principaluri' => 'principals/' . $user,
                '{http://sabredav.org/ns}owner-principal' =>
                    'principals/'
                        . ($share->get('owner')
                           ? $registry->convertUsername($share->get('owner'), false)
                           : '-system-'
                        ),
                '{DAV:}displayname' => Kronolith::getLabel($share),
                '{' . CalDAV\Plugin::NS_CALDAV . '}calendar-description' =>
                    $share->get('desc'),
                '{http://apple.com/ns/ical/}calendar-color' =>
                    $share->get('color') . 'ff',
                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT')),
                '{http://sabredav.org/ns}read-only' => !$share->hasPermission($hordeUser, Horde_Perms::EDIT),
            );
        }
        // External Calendars from other Horde Apps
        foreach ($calendar_manager->get(Kronolith::ALL_EXTERNAL_CALENDARS) as $calendarId => $calendar) {
            // We don't want to duplicate tasks handling and we want no external calendars under -system-
            if ($calendar->api() == 'tasks' || $user == '-system-') {
                continue;
            }
            try {
                $id = $dav->getExternalCollectionId($calendar->internalId(), 'calendar');
            } catch (Horde_Dav_Exception $e) {
            }
            $calendars[] = array(
                'id' => $id,
                'uri' => $id,
                '{' . CalDAV\Plugin::NS_CALENDARSERVER . '}shared-url' =>
                    $calendar->caldavUrl(),
                'principaluri' => 'principals/' . $user,
                '{http://sabredav.org/ns}owner-principal' =>
                    'principals/' . $registry->convertUsername($registry->getAuth(), false),
                '{DAV:}displayname' => sprintf('%s: %s', $registry->get('name', $registry->hasMethod($calendar->api() . '/listTimeObjectCategories' )), $calendar->name()),
                '{http://apple.com/ns/ical/}calendar-color' =>
                    $calendar->background(),
                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT')),
                '{http://sabredav.org/ns}read-only' => true, // For now, externals are readonly
            );
        }


        return $calendars;
    }

    /**
     */
    public function davGetObjects($collection)
    {
        $dav = $GLOBALS['injector']
            ->getInstance('Horde_Dav_Storage');

        $internal = $dav->getInternalCollectionId($collection, 'calendar') ?: $collection;
        // Handle external collections
        $exploded = explode(':', $internal, 2);
        $driverType = null;
        if ($exploded[0] == 'external') {
            $driverType = 'Horde';
            $internalName = str_replace(':', '/', $exploded[1]);
        } elseif (!Kronolith::hasPermission($internal, Horde_Perms::SHOW)) {
            throw new Kronolith_Exception(_("Calendar does not exist or no permission to edit"));
        } else {
            $internalName = $internal;
        }

        $kronolith_driver = Kronolith::getDriver($driverType, $internalName);
        $allEvents = $kronolith_driver->listEvents(
            null,
            null,
            array('cover_dates' => false, 'hide_exceptions' => true)
        );
        $events = array();
        foreach ($allEvents as $dayevents) {
            foreach ($dayevents as $event) {
                $id = $event->id;
                $event->loadHistory();
                $modified = $event->modified ?: $event->created;
                try {
                    $id = $dav->getExternalObjectId($id, $internal) ?: $id . '.ics';
                } catch (Horde_Dav_Exception $e) {
                }
                $events[] = array(
                    'id' => $id,
                    'uri' => $id,
                    'lastmodified' => $modified,
                    'etag' => '"' . md5($event->id . '|' . $modified) . '"',
                    'calendarid' => $collection,
                );
            }
        }

        return $events;
    }

    /**
     */
    public function davGetObject($collection, $object)
    {
        global $calendar_manager;

        $dav = $GLOBALS['injector']
            ->getInstance('Horde_Dav_Storage');

        $internal = $dav->getInternalCollectionId($collection, 'calendar') ?: $collection;
        // Handle external collections
        $exploded = explode(':', $internal, 2);
        $driverType = null;
        if ($exploded[0] == 'external') {
            $driverType = 'Horde';
            $internalName = str_replace(':', '/', $exploded[1]);
        } elseif (!Kronolith::hasPermission($internal, Horde_Perms::SHOW)) {
            throw new Kronolith_Exception(_("Calendar does not exist or no permission to edit"));
        } else {
            $internalName = $internal;
        }

        $kronolith_driver = Kronolith::getDriver($driverType, $internalName);

        try {
            $object = $dav->getInternalObjectId($object, $internal) ?: preg_replace('/\.ics$/', '', $object);
        } catch (Horde_Dav_Exception $e) {
        }
        $event = $kronolith_driver->getEvent($object);
        $id = $event->id;
        try {
            $id = $dav->getExternalObjectId($id, $internal) ?: $id . '.ics';
        } catch (Horde_Dav_Exception $e) {
        }

        $event->loadHistory();
        $modified = $event->modified ?: $event->created;
        $ical = new Horde_Icalendar('2.0');
        if ($exploded[0] == 'external') {
            $calendar = $calendar_manager->getEntry(Kronolith::ALL_EXTERNAL_CALENDARS, $internalName);
            $ical->setAttribute('X-WR-CALNAME', $calendar->name());
        } else {
            $share = $GLOBALS['injector']
                ->getInstance('Kronolith_Shares')
                ->getShare($event->calendar);
            $ical->setAttribute('X-WR-CALNAME', $share->get('name'));
        }

        $ical->addComponent($event->toiCalendar($ical));
        $data = $ical->exportvCalendar();

        return array(
            'id' => $id,
            'calendardata' => $data,
            'uri' => $id,
            'lastmodified' => $modified,
            'etag' => '"' . md5($event->id . '|' . $modified) . '"',
            'calendarid' => $collection,
            'size' => strlen($data),
        );
    }

    /**
     * Add or update an event from DAV
     *
     * @param string $collection  An external collection ID.
     * @param string $object      An external object ID.
     * @param string $data        Icalendar data
     */
    public function davPutObject($collection, $object, $data)
    {
        $dav = $GLOBALS['injector']
            ->getInstance('Horde_Dav_Storage');

        $internal = $dav->getInternalCollectionId($collection, 'calendar') ?: $collection;
        if (!Kronolith::hasPermission($internal, Horde_Perms::EDIT)) {
            throw new Kronolith_Exception(_("Calendar does not exist or no permission to edit"));
        }

        $ical = new Horde_Icalendar();
        if (!$ical->parsevCalendar($data)) {
            throw new Kronolith_Exception(_("There was an error importing the iCalendar data."));
        }
        $importer = new Kronolith_Icalendar_Handler_Dav(
            $ical, Kronolith::getDriver(null, $internal), array('object' => $object)
        );
        $importer->process();
    }

    /**
     */
    public function davDeleteObject($collection, $object)
    {
        $dav = $GLOBALS['injector']->getInstance('Horde_Dav_Storage');

        $internal = $dav->getInternalCollectionId($collection, 'calendar') ?: $collection;
        if (!Kronolith::hasPermission($internal, Horde_Perms::DELETE)) {
            throw new Kronolith_Exception(_("Calendar does not exist or no permission to delete"));
        }

        try {
            $object = $dav->getInternalObjectId($object, $internal)
                ?: preg_replace('/\.ics$/', '', $object);
        } catch (Horde_Dav_Exception $e) {
        }

        $kronolith_driver = Kronolith::getDriver(null, $internal);
        $event = $kronolith_driver->getEvent($object);
        $kronolith_driver->deleteEvent($object);

        try {
            $dav->deleteExternalObjectId($object, $internal);
        } catch (Horde_Dav_Exception $e) {
        }

        // Send iTip messages unless organizer is external.
        // Notifications will get lost, there is no way to return messages to
        // clients.
        if ($event->organizer && !Kronolith::isUserEmail($event->creator, $event->organizer)) {
            return;
        }
        Kronolith::sendITipNotifications(
            $event,
            new Horde_Notification_Handler(new Horde_Notification_Storage_Object()),
            Kronolith::ITIP_CANCEL
        );
    }
}
