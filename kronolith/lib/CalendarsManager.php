<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 * @category Horde
 */

/**
 * Handles management of the various global calendar lists.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 * @category Horde
 */
class Kronolith_CalendarsManager
{
    /**
     * List of all available internal calendars.
     *
     * @var array
     */
    protected $_allCalendars = array();

    /**
     * List of all available remote calendars.
     *
     * @var array
     */
    protected $_allRemote = false;

    /**
     * List of all available external calendars.
     *
     * @var array
     */
    protected $_allExternal = false;

    /**
     * List of all available Holiday calendars.
     *
     * @var array
     */
    protected $_allHolidays = false;

    /**
     * List of all internal calendars that are currently selected to be visible.
     *
     * @var array
     */
    protected $_displayCalendars;

    /**
     * List of remote calendars selected for display.
     *
     * @var array
     */
    protected $_displayRemote;

    /**
     * List of resource calendars selected for display.
     *
     * @var array
     */
    protected $_displayResource;

    /**
     * Lazy loaded list of all resource calendars.
     *
     * @var array.
     */
    protected $_allResource = false;

    /**
     * List of holiday calendars selected for display. Used internally to hold
     * the user prefs for displayed holiday calendars before we need to see
     * if they are all available.
     *
     * @var array
     */
    protected $_displayHolidaysInternal = array();

    /**
     * List of all holidays selected for display.
     *
     * @var array
     */
    protected $_displayHolidays = false;

    /**
     * List of external (listTimeObjects) calendars selected for display.
     *
     * @var array
     */
    protected $_displayExternal = false;

    /**
     * Const'r
     * Sets up various display lists and session variables:
     *
     * Always set:
     *  - allCalendars
     *  - displayCalendars
     *  - displayResource
     *
     * Lazy loaded:
     *  - allRemote
     *  - allExternal
     *  - allHolidays
     *  - allResource
     *  - displayRemote
     *  - displayExternal
     *  - displayHolidays
     *
     * @param string $user  The user to initialize for, if not the current.
     *                      @since 4.2.4
     */
    public function __construct($user = null)
    {
        $emptyUser = false;
        if (empty($user)) {
            $user = $GLOBALS['registry']->getAuth();
            $emptyUser = true;
        }
        // Always perform the display related checks.
        $this->_checkDisplayCals();
        $this->_checkToggleCalendars();

        // Check that all selected shares still exist.
        foreach (Kronolith::listInternalCalendars(false, Horde_Perms::SHOW, $user) as $id => $calendar) {
            $this->_allCalendars[$id] = new Kronolith_Calendar_Internal(array('share' => $calendar));
        }
        $this->_displayCalendars = array_intersect($this->_displayCalendars, array_keys($this->_allCalendars));

        // Check that the user owns a calendar if we aren't loading a different
        // user.
        if ($emptyUser) {
            $this->_checkForOwnedCalendar();
        }
    }

    /**
     * Return the requested list.
     *
     * @param string $list  A Kronolith:: calendar manager constant.
     *
     * @return array
     */
    public function get($list)
    {
        switch ($list) {
        case Kronolith::ALL_CALENDARS:
        case Kronolith::DISPLAY_CALENDARS:
        case Kronolith::DISPLAY_RESOURCE_CALENDARS:
            $property = '_' . $list;
            return $this->$property;

        //Lazy loaded
        case Kronolith::ALL_RESOURCE_CALENDARS:
            if ($this->_allResource !== false) {
                return $this->_allResource;
            }
            return $this->_getAllResource();

        case Kronolith::ALL_REMOTE_CALENDARS:
            if ($this->_allRemote !== false) {
                return $this->_allRemote;
            }
            return $this->_getRemoteCalendars();

        case Kronolith::DISPLAY_REMOTE_CALENDARS:
            // Need to run this at least once to validate remote calendars
            // still exist in prefs.
            if ($this->_allRemote === false) {
                $this->_getRemoteCalendars();
            }
            return $this->_displayRemote;

        case Kronolith::ALL_EXTERNAL_CALENDARS:
            if ($this->_allExternal !== false) {
                return $this->_allExternal;
            }
            return $this->_getAllExternal();

        case Kronolith::DISPLAY_EXTERNAL_CALENDARS:
            if ($this->_displayExternal !== false) {
                return $this->_displayExternal;
            }
            return $this->_getDisplayExternal();

        case Kronolith::ALL_HOLIDAYS:
            if ($this->_allHolidays !== false) {
                return $this->_allHolidays;
            }
            return $this->_getAllHolidays();

        case Kronolith::DISPLAY_HOLIDAYS:
            if ($this->_displayHolidays !== false) {
                return $this->_displayHolidays;
            }
            return $this->_getDisplayHolidays();
        }
    }

    /**
     * Set or replace an existing list with $value.
     *
     * @param string $list   The list to set.
     * @param array  $value  The value to set it to.
     */
    public function set($list, array $value)
    {
        $property = '_' . $list;
        $this->$property = $value;
    }

    /**
     * Shortcut method for obtaining a single entry in one of the calendar lists
     *
     * @param string $list   The calendar list to obtain an entry from.
     * @param string $entry  The entry to retrieve.
     *
     * @return mixed  The requested value | false if not found.
     */
    public function getEntry($list, $entry)
    {
        $temp = $this->get($list);
        if (isset($temp[$entry])) {
            return $temp[$entry];
        }

        return false;
    }

    /**
     * Update display preferences
     */
    protected function _checkDisplayCals()
    {
        global $session, $prefs, $conf;

        // Update preferences for which calendars to display. If the
        // user doesn't have any selected calendars to view then fall
        // back to an available calendar. An empty string passed in this
        // parameter will clear any existing session value.
        if (($calId = Horde_Util::getFormData('display_cal')) !== null) {
            $session->set('kronolith', 'display_cal', $calId);
        } else {
            $calId = $session->get('kronolith', 'display_cal');
        }

        if (strlen($calId)) {
            // Specifying a value for display_cal is always to make sure
            // that only the specified calendars are shown. Use the
            // "toggle_calendar" argument to toggle the state of a single
            // calendar.
            $this->_displayCalendars = array();
            $this->_displayRemote = array();
            $this->_displayExternal = array();
            $this->_displayResource = array();
            $this->_displayHolidaysInternal = array();

            if (strncmp($calId, 'remote_', 7) === 0) {
                $this->_displayRemote[] = substr($calId, 7);
            } elseif (strncmp($calId, 'external_', 9) === 0) {
                $this->_displayExternal[] = substr($calId, 9);
            } elseif (strncmp($calId, 'resource_', 9) === 0) {
                $this->_displayResource[] = substr($calId, 9);
            } elseif (strncmp($calId, 'holidays_', 9) === 0) {
                $this->_displayHolidaysInternal[] = substr($calId, 9);
            } else {
                $this->_displayCalendars[] = (strncmp($calId, 'internal_', 9) === 0)
                    ? substr($calId, 9)
                    : $calId;
            }
        } else {
            // Fetch display preferences.
            $display_prefs = array(
                'display_cals' => 'displayCalendars',
                'display_remote_cals' => 'displayRemote',
                'display_external_cals' => 'displayExternal',
                'holiday_drivers' => 'displayHolidaysInternal',
                'display_resource_cals' => 'displayResource'
            );
            foreach ($display_prefs as $key => $val) {
                $pref_val = @unserialize($prefs->getValue($key));
                $val = '_' . $val;
                $this->$val = is_array($pref_val)
                    ? $pref_val
                    : array();
            }
            if (empty($conf['holidays']['enable'])) {
                $this->_displayHolidays = array();
                $this->_displayHolidaysInternal = array();
            }
        }
    }

    /**
     * Check for single, "toggle" calendars and set display lists and
     * session values appropriately.
     */
    protected function _checkToggleCalendars()
    {
        global $prefs, $registry;

        if (($calId = Horde_Util::getFormData('toggle_calendar')) !== null) {
            if (strncmp($calId, 'remote_', 7) === 0) {
                $calId = substr($calId, 7);
                if (($key = array_search($calId, $this->_displayRemote)) === false) {
                    $this->_displayRemote[] = $calId;
                } else {
                    unset($this->_displayRemote[$key]);
                }
                $prefs->setValue('display_remote_cals', serialize($this->_displayRemote));
            } elseif ((strncmp($calId, 'external_', 9) === 0 &&
                       ($calId = substr($calId, 9))) ||
                      (strncmp($calId, 'tasklists_', 10) === 0 &&
                       ($calId = substr($calId, 10)))) {
                if (($key = array_search($calId, $this->_displayExternal)) === false) {
                    $this->_displayExternal[] = $calId;
                } else {
                    unset($this->_displayExternal[$key]);
                }
                $prefs->setValue('display_external_cals', serialize($this->_displayExternal));

                if (strpos($calId, 'tasks/') === 0) {
                    $tasklists = array();
                    foreach ($this->_displayExternal as $id) {
                        if (strpos($id, 'tasks/') === 0) {
                            $tasklists[] = substr($id, 6);
                        }
                    }
                    try {
                        $registry->tasks->setDisplayedTasklists($tasklists);
                    } catch (Horde_Exception $e) {}
                }
            } elseif (strncmp($calId, 'holiday_', 8) === 0) {
                $calId = substr($calId, 8);
                if (($key = array_search($calId, $this->_displayHolidaysInternal)) === false) {
                    $this->_displayHolidaysInternal[] = $calId;
                } else {
                    unset($this->_displayHolidaysInternal[$key]);
                }
                $prefs->setValue('holiday_drivers', serialize($this->_displayHolidaysInternal));
            } elseif (strncmp($calId, 'resource_', 9) === 0) {
                $calId = substr($calId, 9);
                if (($key = array_search($calId, $this->_displayResource)) === false) {
                    $this->_displayResource[] = $calId;
                } else {
                    unset($this->_displayResource[$key]);
                }
                $prefs->setValue('display_resource_cals', serialize($this->_displayResource));
            } elseif (($key = array_search($calId, $this->_displayCalendars)) === false) {
                $this->_displayCalendars[] = $calId;
            } else {
                unset($this->_displayCalendars[$key]);
            }

            $prefs->setValue('display_cals', serialize($this->_displayCalendars));
        }
    }

    /**
     * Check that the user owns a calendar and if not, creates one.
     */
    protected function _checkForOwnedCalendar()
    {
        global $prefs, $registry, $conf;

        if (!empty($conf['share']['auto_create']) &&
            $registry->getAuth() &&
            !count(Kronolith::listInternalCalendars(true))) {
            $calendars = $GLOBALS['injector']
                ->getInstance('Kronolith_Factory_Calendars')
                ->create();

            $share = $calendars->createDefaultShare();
            $this->_allCalendars[$share->getName()] = new Kronolith_Calendar_Internal(array('share' => $share));
            $this->_displayCalendars[] = $share->getName();
            $prefs->setValue('default_share', $share->getName());

            // Calendar auto-sharing with the user's groups
            if ($conf['autoshare']['shareperms'] != 'none') {
                $perm_value = 0;
                switch ($conf['autoshare']['shareperms']) {
                case 'read':
                    $perm_value = Horde_Perms::READ | Horde_Perms::SHOW;
                    break;
                case 'edit':
                    $perm_value = Horde_Perms::READ | Horde_Perms::SHOW | Horde_Perms::EDIT;
                    break;
                case 'full':
                    $perm_value = Horde_Perms::READ | Horde_Perms::SHOW | Horde_Perms::EDIT | Horde_Perms::DELETE;
                    break;
                }

                try {
                    $group_list = $GLOBALS['injector']
                        ->getInstance('Horde_Group')
                        ->listGroups($registry->getAuth());
                    if (count($group_list)) {
                        $perm = $share->getPermission();
                        // Add the default perm, not added otherwise
                        foreach (array_keys($group_list) as $group_id) {
                            $perm->addGroupPermission($group_id, $perm_value, false);
                        }
                        $share->setPermission($perm);
                        $GLOBALS['notification']->push(sprintf(_("New calendar created and automatically shared with the following group(s): %s."), implode(', ', $group_list)), 'horde.success');
                    }
                } catch (Horde_Group_Exception $e) {}
            }

            $prefs->setValue('display_cals', serialize($this->_displayCalendars));
        }
    }

    /**
     * Return all known external calendars.
     *
     * @return array
     */
    protected function _getAllExternal()
    {
        global $registry, $session;

        $this->_allExternal = array();

       // Make sure all task lists exist.
        if (Kronolith::hasApiPermission('tasks') &&
            $registry->hasMethod('tasks/listTimeObjects')) {
            try {
                $tasklists = $registry->tasks->listTasklists();
                $categories = $registry->call('tasks/listTimeObjectCategories');
                foreach ($categories as $name => $description) {
                    if (!isset($tasklists[$name])) {
                        continue;
                    }
                    $this->_allExternal['tasks/' . $name] = new Kronolith_Calendar_External_Tasks(array('api' => 'tasks', 'name' => $description['title'], 'share' => $tasklists[$name], 'type' => 'share'));
                }
            } catch (Horde_Exception $e) {
                Horde::log($e, 'DEBUG');
            }
        }

        if ($session->exists('kronolith', 'all_external_calendars')) {
            foreach ($session->get('kronolith', 'all_external_calendars') as $calendar) {
                if (!Kronolith::hasApiPermission($calendar['a']) ||
                    $calendar['a'] == 'tasks') {
                    continue;
                }
                $this->_allExternal[$calendar['a'] . '/' . $calendar['n']] = new Kronolith_Calendar_External(array('api' => $calendar['a'], 'name' => $calendar['d'], 'id' => $calendar['n'], 'type' => $calendar['t'], 'background' => $calendar['b']));
            }
        } else {
            $apis = array_unique($registry->listAPIs());
            $ext_cals = array();

            foreach ($apis as $api) {
                if ($api == 'tasks' ||
                    !Kronolith::hasApiPermission($api) ||
                    !$registry->hasMethod($api . '/listTimeObjects')) {
                    continue;
                }
                try {
                    $categories = $registry->call($api . '/listTimeObjectCategories');
                } catch (Horde_Exception $e) {
                    Horde::log($e, 'DEBUG');
                    continue;
                }

                foreach ($categories as $name => $description) {
                    $this->_allExternal[$api . '/' . $name] = new Kronolith_Calendar_External(array('api' => $api, 'name' => $description['title'], 'id' => $name, 'type' => $description['type']));
                    $ext_cals[] = array(
                        'a' => $api,
                        'n' => $name,
                        'd' => $description['title'],
                        't' => $description['type'],
                        'b' => empty($description['background']) ? '#dddddd' : $description['background']
                    );
                }
            }

            $session->set('kronolith', 'all_external_calendars', $ext_cals);
        }

        return $this->_allExternal;
    }

    /**
     * Return all external calendars selected for display.
     *
     * @return array
     */
    protected function _getDisplayExternal()
    {
        global $registry, $prefs;

       // Make sure all the external calendars still exist.
        $_tasklists = $_temp = $this->_displayExternal;
        if (Kronolith::hasApiPermission('tasks')) {
            try {
                $_tasklists = $registry->tasks->getDisplayedTasklists();
            } catch (Horde_Exception $e) {
            }
        }
        $this->_displayExternal = array();
        foreach ($this->get(Kronolith::ALL_EXTERNAL_CALENDARS) as $id => $calendar) {
            if ((substr($id, 0, 6) == 'tasks/' &&
                 in_array(substr($id, 6), $_tasklists)) ||
                in_array($id, $_temp)) {
                $this->_displayExternal[] = $id;
            }
        }
        $prefs->setValue('display_external_cals', serialize($this->_displayExternal));

        return $this->displayExternal;
    }

    /**
     * Return list of all available holidays drivers.
     *
     * @return array  The available holidays.
     */
    protected function _getAllHolidays()
    {
        $this->_allHolidays = array();
        if (!empty($GLOBALS['conf']['holidays']['enable'])) {
            if (class_exists('Date_Holidays')) {
                $dh = new Date_Holidays();
                foreach ($dh->getInstalledDrivers() as $driver) {
                    if ($driver['id'] == 'Composite') {
                        continue;
                    }
                    $this->_allHolidays[$driver['id']] = new Kronolith_Calendar_Holiday(array('driver' => $driver));
                    ksort($this->_allHolidays);
                }
            }
        }

        return $this->_allHolidays;
    }

    /**
     * Return list of holiday calendars to be displayed.
     *
     * @return array  The holiday calendars to display.
     */
    protected function _getDisplayHolidays()
    {
        $this->_displayHolidays = array();
        foreach (array_keys($this->get(Kronolith::ALL_HOLIDAYS)) as $id) {
            if (in_array($id, $this->_displayHolidaysInternal)) {
                $this->_displayHolidays[] = $id;
            }
        }
        $GLOBALS['prefs']->setValue('holiday_drivers', serialize($this->_displayHolidays));

        return $this->_displayHolidays;
    }

    /**
     * Return list of all resource calendars.
     *
     * @return array  Resource calendars, keyed by calendar id.
     */
    protected function _getAllResource()
    {
        $this->_allResource = array();
        if (!empty($GLOBALS['conf']['resource']['driver'])) {
            foreach (Kronolith::getDriver('Resource')->listResources(Horde_Perms::READ, array('type' => Kronolith_Resource::TYPE_SINGLE)) as $resource) {
                $rcal = new Kronolith_Calendar_Resource(array(
                    'resource' => $resource
                ));
                $this->_allResource[$resource->get('calendar')] = $rcal;
            }
        }

        return $this->_allResource;
    }

    protected function _getRemoteCalendars()
    {
        if ($this->_allRemote === false) {
            // Check that all selected remote calendars are still configured.
            $tmp = $this->_displayRemote;
            $this->_allRemote = $this->_displayRemote = array();
            $calendars = @unserialize($GLOBALS['prefs']->getValue('remote_cals'));
            if (!is_array($calendars)) {
                $calendars = array();
            }
            foreach ($calendars as $calendar) {
                $this->_allRemote[$calendar['url']] = new Kronolith_Calendar_Remote($calendar);
                if (in_array($calendar['url'], $tmp)) {
                    $this->_displayRemote[] = $calendar['url'];
                }
            }
            $GLOBALS['prefs']->setValue('display_remote_cals', serialize($this->_displayRemote));
        }

        return $this->_allRemote;
    }

}
