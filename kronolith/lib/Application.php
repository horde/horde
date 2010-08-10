<?php
/**
 * Kronolith application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Kronolith through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/* Determine the base directories. */
if (!defined('KRONOLITH_BASE')) {
    define('KRONOLITH_BASE', dirname(__FILE__) . '/..');
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
     * Does this application support an ajax view?
     *
     * @var boolean
     */
    public $ajaxView = true;

    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $kronolith_shares - TODO
     */
    protected function _init()
    {
        /* For now, autoloading the Content_* classes depend on there being a
         * registry entry for the 'content' application that contains at least
         * the fileroot entry. */
        $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the registry entry for the Content system is present.');
        }

        /* Set the timezone variable, if available. */
        $GLOBALS['registry']->setTimeZone();

        /* Create a share instance. */
        $GLOBALS['kronolith_shares'] = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();

        Kronolith::initialize();
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array();
        $perms['tree']['kronolith']['max_events'] = false;
        $perms['title']['kronolith:max_events'] = _("Maximum Number of Events");
        $perms['type']['kronolith:max_events'] = 'int';

        return $perms;
    }

    /**
     * Returns the specified permission for the given app permission.
     *
     * @param string $permission  The permission to check.
     * @param mixed $allowed      The allowed permissions.
     * @param array $opts         Additional options (NONE).
     *
     * @return mixed  The value of the specified permission.
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        switch ($permission) {
        case 'max_events':
            $allowed = max($allowed);
            break;
        }

        return $allowed;
    }

    /**
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsEnum($ui)
    {
        global $conf, $prefs, $registry;

        switch ($ui->group) {
        case 'freebusy':
            if (!$prefs->isLocked('fb_cals')) {
                $fb_list = array();
                foreach (Kronolith::listCalendars() as $fb_cal => $cal) {
                    $fb_list[htmlspecialchars($fb_cal)] = htmlspecialchars($cal->get('name'));
                }
                $ui->override['fb_cals'] = $fb_list;
            }
            break;

       case 'share':
            if (!$prefs->isLocked('default_share')) {
                $all_shares = Kronolith::listInternalCalendars();
                $sharelist = array();

                foreach ($all_shares as $id => $share) {
                    if (!empty($conf['share']['hidden']) &&
                        ($share->get('owner') != $GLOBALS['registry']->getAuth()) &&
                        !in_array($share->getName(), $GLOBALS['display_calendars'])) {
                        continue;
                    }
                    $sharelist[$id] = $share;
                }

                $vals = array();
                foreach ($sharelist as $id => $share) {
                    $vals[htmlspecialchars($id)] = htmlspecialchars($share->get('name'));
                }
                $ui->override['default_share'] = $vals;
            }
            break;

        case 'view':
            if (!$prefs->isLocked('day_hour_start') ||
                !$prefs->isLocked('day_hour_end')) {
                $out = array();
                $tf = $GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia';
                for ($i = 0; $i <= 48; ++$i) {
                    $out[$i] = date($tf, mktime(0, $i * 30, 0));
                }
                $ui->override['day_hour_end'] = $out;
                $ui->override['day_hour_start'] = $out;
            }
            break;
        }
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $conf, $prefs, $registry;

        switch ($ui->group) {
        case 'addressbooks':
            if (!$prefs->isLocked('sourceselect')) {
                Horde_Core_Prefs_Ui_Widgets::addressbooksInit();
            }
            break;

        case 'notification':
            if (empty($conf['alarms']['driver']) ||
                $prefs->isLocked('event_alarms') ||
                $prefs->isLocked('event_alarms_select')) {
                $ui->suppress[]= 'event_alarms';
            } else {
                Horde_Core_Prefs_Ui_Widgets::alarminit();
            }
            break;
        }

        /* Suppress prefGroups display. */
        if (!$registry->hasMethod('contacts/sources')) {
            $ui->suppressGroups[] = 'addressbooks';
        }

        if ($prefs->isLocked('default_alarm')) {
            $ui->suppressGroups[] = 'event_options';
        }
    }

   /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the options page.
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'default_alarm_management':
            return $this->_defaultAlarmManagement($ui);

        case 'event_alarms_select':
            return Horde_Core_Prefs_Ui_Widgets::alarm(array(
                'label' => _("Choose how you want to receive reminders for events with alarms:"),
                'pref' => 'event_alarms'
            ));

        case 'sourceselect':
            $search = Kronolith::getAddressbookSearchParams();
            return Horde_Core_Prefs_Ui_Widgets::addressbooks(array(
                'fields' => $search['fields'],
                'sources' => $search['sources']
            ));
        }

        return '';
    }

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        switch ($item) {
        case 'default_alarm_management':
            $GLOBALS['prefs']->setValue('default_alarm', (int)$ui->vars->alarm_value * (int)$ui->vars->alarm_unit);
            return true;

        case 'event_alarms_select':
            $data = Horde_Core_Prefs_Ui_Widgets::alarmUpdate($ui, array('pref' => 'event_alarms'));
            if (!is_null($data)) {
                $GLOBALS['prefs']->setValue('event_alarms', serialize($data));
                return true;
            }
            break;

        case 'remote_cal_management':
            return $this->_prefsRemoteCalManagement($ui);

        case 'sourceselect':
            return $this->_prefsSourceselect($ui);
        }

        return false;
    }

    /**
     * Called when preferences are changed.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsCallback($ui)
    {
        if ($GLOBALS['prefs']->isDirty('event_alarms')) {
            try {
                $alarms = $GLOBALS['registry']->callByPackage('kronolith', 'listAlarms', array($_SERVER['REQUEST_TIME']));
                if (!empty($alarms)) {
                    $horde_alarm = $GLOBALS['injector']->getInstance('Horde_Alarm');
                    foreach ($alarms as $alarm) {
                        $alarm['start'] = new Horde_Date($alarm['start']);
                        $alarm['end'] = new Horde_Date($alarm['end']);
                        $horde_alarm->set($alarm);
                    }
                }
            } catch (Exception $e) {}
        }
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu($ui)
    {
        return Kronolith::getMenu();
    }

    /**
     * Create code for default alarm management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _defaultAlarmManagement($ui)
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if ($alarm_value = $GLOBALS['prefs']->getValue('default_alarm')) {
            if ($alarm_value % 10080 == 0) {
                $alarm_value /= 10080;
                $t->set('week', true);
            } elseif ($alarm_value % 1440 == 0) {
                $alarm_value /= 1440;
                $t->set('day', true);
            } elseif ($alarm_value % 60 == 0) {
                $alarm_value /= 60;
                $t->set('hour', true);
            } else {
                $t->set('minute', true);
            }
        } else {
            $t->set('minute', true);
        }

        $t->set('alarm_value', intval($alarm_value));

        return $t->fetch(KRONOLITH_TEMPLATES . '/prefs/defaultalarm.html');
    }

    /**
     * Create code for remote calendar management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _prefsRemoteCalManagement($ui)
    {
        $calName = $ui->vars->remote_name;
        $calUrl  = trim($ui->vars->remote_url);
        $calUser = trim($ui->vars->remote_user);
        $calPasswd = trim($ui->vars->remote_password);

        $key = $GLOBALS['registry']->getAuthCredential('password');
        if ($key) {
            $calUser = base64_encode(Secret::write($key, $calUser));
            $calPasswd = base64_encode(Secret::write($key, $calPasswd));
        }

        $calActionID = isset($ui->vars->remote_action)
            ? $ui->vars->remote_action
            : 'add';

        if ($calActionID == 'add') {
            if (!empty($calName) && !empty($calUrl)) {
                $cals = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
                $cals[] = array('name' => $calName,
                    'url'  => $calUrl,
                    'user' => $calUser,
                    'password' => $calPasswd);
                $GLOBALS['prefs']->setValue('remote_cals', serialize($cals));
            }
        } elseif ($calActionID == 'delete') {
            $cals = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
            foreach ($cals as $key => $cal) {
                if ($cal['url'] == $calUrl) {
                    unset($cals[$key]);
                    break;
                }
            }
            $GLOBALS['prefs']->setValue('remote_cals', serialize($cals));
        } elseif ($calActionID == 'edit') {
            $cals = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
            foreach ($cals as $key => $cal) {
                if ($cal['url'] == $calUrl) {
                    $cals[$key]['name'] = $calName;
                    $cals[$key]['url'] = $calUrl;
                    $cals[$key]['user'] = $calUser;
                    $cals[$key]['password'] = $calPasswd;
                    break;
                }
            }
            $GLOBALS['prefs']->setValue('remote_cals', serialize($cals));
        }
    }

    /**
     * Update address book related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _prefsSourceselect($ui)
    {
        global $prefs;

        $data = Horde_Core_Prefs_Ui_Widgets::addressbooksUpdate($ui);
        $updated = false;

        if (isset($data['sources'])) {
            $prefs->setValue('search_sources', $data['sources']);
            $updated = true;
        }

        if (isset($data['fields'])) {
            $prefs->setValue('search_fields', $data['fields']);
            $updated = true;
        }

        return $updated;
    }

    /**
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @throws Kronolith_Exception
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function removeUserData($user)
    {
        if (!$GLOBALS['registry']->isAdmin() &&
            $user != $GLOBALS['registry']->getAuth()) {
            throw new Kronolith_Exception(_("You are not allowed to remove user data."));
        }

        /* Remove all events owned by the user in all calendars. */
        $result = Kronolith::getDriver()->removeUserData($user);

        /* Get the user's default share */
        try {
            $share = $GLOBALS['kronolith_shares']->getShare($user);
            $result = $GLOBALS['kronolith_shares']->removeShare($share);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        /* Get a list of all shares this user has perms to and remove the
         * perms */
        try {
            $shares = $GLOBALS['kronolith_shares']->listShares($user);
            foreach ($shares as $share) {
                $share->removeUser($user);
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }
    }

}
