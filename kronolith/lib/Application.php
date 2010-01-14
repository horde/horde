<?php
/**
 * Kronolith application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
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
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $kronolith_notify - A Horde_Notification_Listener object
     *   $kronolith_shares - TODO
     *   $notification - Notification object
     *
     * Global constants defined:
     *   KRONOLITH_TEMPLATES - (string) Location of template files.
     *
     * When calling Horde_Registry::appInit(), the following parameters are
     * also supported:
     * <pre>
     * 'user' - (string) Set authentication to this user.
     * </pre>
     */
    protected function _init()
    {
        if (isset($this->initParams['user'])) {
            Horde_Auth::setAuth($this->initParams['user'], array());
        }

        if (!defined('KRONOLITH_TEMPLATES')) {
            define('KRONOLITH_TEMPLATES', $GLOBALS['registry']->get('templates'));
        }

        /* For now, autoloading the Content_* classes depend on there being a
         * registry entry for the 'content' application that contains at least
         * the fileroot entry. */
        Horde_Autoloader::addClassPattern('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/');
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the registry entry for the Content system is present.');
        }

        /* Notification system. */
        $GLOBALS['notification'] = Horde_Notification::singleton();
        $GLOBALS['kronolith_notify'] = $GLOBALS['notification']->attach('status', null, 'Kronolith_Notification_Listener_Status');

        /* Set the timezone variable, if available. */
        Horde_Nls::setTimeZone();

        /* Create a share instance. */
        $GLOBALS['kronolith_shares'] = Horde_Share::singleton($GLOBALS['registry']->getApp());

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
     * Returns the specified permission for the current user.
     *
     * @param mixed $allowed  The allowed permissions.
     *
     * @return mixed  The value of the specified permission.
     */
    public function hasPermission($allowed)
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
     * Code to run when viewing prefs for this application.
     *
     * @param string $group  The prefGroup name.
     *
     * @return array  A list of variables to export to the prefs display page.
     */
    public function prefsInit($group)
    {
        $out = array();

        if (!$GLOBALS['prefs']->isLocked('day_hour_start') ||
            !$GLOBALS['prefs']->isLocked('day_hour_end')) {
            $out['day_hour_start_options'] = array();
            for ($i = 0; $i <= 48; ++$i) {
                $out['day_hour_start_options'][$i] = date(($GLOBALS['prefs']->getValue('twentyFour')) ? 'G:i' : 'g:ia', mktime(0, $i * 30, 0));
            }
            $out['day_hour_end_options'] = $out['day_hour_start_options'];
        }

        return $out;
    }

    /**
     * Special preferences handling on update.
     *
     * @param string $item      The preference name.
     * @param boolean $updated  Set to true if preference was updated.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecial($item, $updated)
    {
        switch ($item) {
        case 'remote_cal_management':
            return $this->_prefsRemoteCalManagement($updated);

        case 'shareselect':
            return $this->_prefsShareSelect($updated);

        case 'sourceselect':
            return $this->_prefsSourceSelect($updated);

        case 'fb_cals_select':
            $this->_prefsFbCalsSelect($updated);
            return true;

        case 'default_alarm_management':
            $GLOBALS['prefs']->setValue('default_alarm', (int)Horde_Util::getFormData('alarm_value') * (int)Horde_Util::getFormData('alarm_unit'));
            return true;
        }
    }

    /**
     * Do anything that we need to do as a result of certain preferences
     * changing.
     */
    public function prefsCallback()
    {
        if ($GLOBALS['prefs']->isDirty('event_alarms')) {
            $alarms = $GLOBALS['registry']->callByPackage('kronolith', 'listAlarms', array($_SERVER['REQUEST_TIME']));
            if (!is_a($alarms, 'PEAR_Error') && !empty($alarms)) {
                $horde_alarm = Horde_Alarm::factory();
                foreach ($alarms as $alarm) {
                    $alarm['start'] = new Horde_Date($alarm['start']);
                    $alarm['end'] = new Horde_Date($alarm['end']);
                    $horde_alarm->set($alarm);
                }
            }
        }
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Kronolith::getMenu();
    }

    /**
     * TODO
     */
    protected function _prefsRemoteCalManagement($updated)
    {
        $calName = Horde_Util::getFormData('remote_name');
        $calUrl  = trim(Horde_Util::getFormData('remote_url'));
        $calUser = trim(Horde_Util::getFormData('remote_user'));
        $calPasswd = trim(Horde_Util::getFormData('remote_password'));

        $key = Horde_Auth::getCredential('password');
        if ($key) {
            $calUser = base64_encode(Secret::write($key, $calUser));
            $calPasswd = base64_encode(Secret::write($key, $calPasswd));
        }

        $calActionID = Horde_Util::getFormData('remote_action', 'add');

        if ($calActionID == 'add') {
            if (!empty($calName) && !empty($calUrl)) {
                $cals = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
                $cals[] = array('name' => $calName,
                    'url'  => $calUrl,
                    'user' => $calUser,
                    'password' => $calPasswd);
                $GLOBALS['prefs']->setValue('remote_cals', serialize($cals));
                return $updated;
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
            return $updated;
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
            return $updated;
        }

        return false;
    }

    /**
     * TODO
     */
    protected function _prefsShareSelect($updated)
    {
        $default_share = Horde_Util::getFormData('default_share');
        if (!is_null($default_share)) {
            $sharelist = Kronolith::listCalendars();
            if ((is_array($sharelist)) > 0 &&
                isset($sharelist[$default_share])) {
                $GLOBALS['prefs']->setValue('default_share', $default_share);
                return true;
            }
        }

        return $updated;
    }

    /**
     * TODO
     */
    protected function _prefsSourceSelect($updated)
    {
        $search_sources = Horde_Util::getFormData('search_sources');
        if (!is_null($search_sources)) {
            $GLOBALS['prefs']->setValue('search_sources', $search_sources);
            $updated = true;
        }

        $search_fields_string = Horde_Util::getFormData('search_fields_string');
        if (!is_null($search_fields_string)) {
            $GLOBALS['prefs']->setValue('search_fields', $search_fields_string);
            $updated = true;
        }

        return $updated;
    }

    /**
     * TODO
     */
    protected function _prefsFbCalsSelect()
    {
        $fb_calsSelected = Horde_Util::getFormData('fb_cals');
        $fb_cals = Kronolith::listCalendars();
        $fb_calsFiltered = array();

        if (isset($fb_calsSelected) && is_array($fb_calsSelected)) {
            foreach ($fb_calsSelected as $fb_cal) {
                $fb_calsFiltered[] = $fb_cal;
            }
        }

        $GLOBALS['prefs']->setValue('fb_cals', serialize($fb_calsFiltered));
    }

    /**
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @return mixed  true on success | PEAR_Error on failure
     */
    public function removeUserData($user)
    {
        if (!Horde_Auth::isAdmin() && $user != Horde_Auth::getAuth()) {
            return PEAR::raiseError(_("You are not allowed to remove user data."));
        }

        /* Remove all events owned by the user in all calendars. */
        $result = Kronolith::getDriver()->removeUserData($user);

        /* Now delete history as well. */
        $history = Horde_History::singleton();
        if (method_exists($history, 'removeByParent')) {
            $histories = $history->removeByParent('kronolith:' . $user);
        } else {
            /* Remove entries 100 at a time. */
            $all = $history->getByTimestamp('>', 0, array(), 'kronolith:' . $user);
            if (is_a($all, 'PEAR_Error')) {
                Horde::logMessage($all, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                $all = array_keys($all);
                while (count($d = array_splice($all, 0, 100)) > 0) {
                    $history->removebyNames($d);
                }
            }
        }

        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Get the user's default share */
        $share = $GLOBALS['kronolith_shares']->getShare($user);
        if (is_a($share, 'PEAR_Error')) {
            Horde::logMessage($share, __FILE__, __LINE__, PEAR_LOG_ERR);
        } else {
            $result = $GLOBALS['kronolith_shares']->removeShare($share);
            if (is_a($result, 'PEAR_Error')) {
                $hasError = true;
                Horde::logMessage($result->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        }

        /* Get a list of all shares this user has perms to and remove the perms */
        $shares = $GLOBALS['kronolith_shares']->listShares($user);
        if (is_a($shares, 'PEAR_Error')) {
            Horde::logMessage($shares, __FILE__, __LINE__, PEAR_LOG_ERR);
        }
        foreach ($shares as $share) {
            $share->removeUser($user);
        }

        return true;
    }

}
