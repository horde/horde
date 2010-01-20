<?php
/**
 * Nag application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Nag
 */

/* Determine the base directories. */
if (!defined('NAG_BASE')) {
    define('NAG_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(NAG_BASE . '/config/horde.local.php')) {
        include NAG_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', NAG_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Nag_Application extends Horde_Registry_Application
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
     *   $nag_shares - TODO
     */
    protected function _init()
    {
       // Set the timezone variable.
       Horde_Nls::setTimeZone();

       // Create a share instance.
       $GLOBALS['nag_shares'] = Horde_Share::singleton($GLOBALS['registry']->getApp());

       Nag::initialize();
    }

    /**
     * Initialization for Notification system.
     *
     * Global variables defined:
     *   $kronolith_notify - A Horde_Notification_Listener object.
     *
     * @param Horde_Notification_Handler_Base $notify  The notification
     *                                                 object.
     */
    protected function _initNotification($notify)
    {
        $notify->attach('status', null, 'Nag_Notification_Listener_Status');
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array();
        $perms['tree']['nag']['max_tasks'] = false;
        $perms['title']['nag:max_tasks'] = _("Maximum Number of Tasks");
        $perms['type']['nag:max_tasks'] = 'int';

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
            case 'max_tasks':
                $allowed = max($allowed);
                break;
            }
        }

        return $allowed;
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
        case 'tasklistselect':
            $default_tasklist = Horde_Util::getFormData('default_tasklist');
            if (!is_null($default_tasklist)) {
                $tasklists = Nag::listTasklists();
                if (is_array($tasklists) &&
                    isset($tasklists[$default_tasklist])) {
                    $GLOBALS['prefs']->setValue('default_tasklist', $default_tasklist);
                    return true;
                }
            }
            break;

        case 'showsummaryselect':
            $GLOBALS['prefs']->setValue('summary_categories', Horde_Util::getFormData('summary_categories'));
            return true;

        case 'defaultduetimeselect':
            $GLOBALS['prefs']->setValue('default_due_time', Horde_Util::getFormData('default_due_time'));
            return true;
        }

        return $updated;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Nag::getMenu();
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

        /* Error flag */
        $hasError = false;

        /* Get the share for later deletion */
        $share = $GLOBALS['nag_shares']->getShare($user);
        if(is_a($share, 'PEAR_Error')) {
            Horde::logMessage($share->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            unset($share);
        } else {
            /* Get the list of all tasks */
            $tasks = Nag::listTasks(null, null, null, $user, 1);
            if (is_a($tasks, 'PEAR_Error')) {
                $hasError = true;
                Horde::logMessage($share->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                $uids = array();
                $tasks->reset();
                while ($task = $tasks->each()) {
                    $uids[] = $task->uid;
                }

                /* ... and delete them. */
                foreach ($uids as $uid) {
                    $this->delete($uid);
                }
            }
        }

        /* Now delete history as well. */
        $history = Horde_History::singleton();
        if (method_exists($history, 'removeByParent')) {
            $histories = $history->removeByParent('nag:' . $user);
        } else {
            /* Remove entries 100 at a time. */
            $all = $history->getByTimestamp('>', 0, array(), 'nag:' . $user);
            if (is_a($all, 'PEAR_Error')) {
                Horde::logMessage($all, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                $all = array_keys($all);
                while (count($d = array_splice($all, 0, 100)) > 0) {
                    $history->removebyNames($d);
                }
            }
        }

        /* ...and finally, delete the actual share */
        if (!empty($share)) {
            $result = $GLOBALS['nag_shares']->removeShare($share);
            if (is_a($result, 'PEAR_Error')) {
                $hasError = true;
                Horde::logMessage($result->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        }

        /* Now remove perms for this user from all other shares */
        $shares = $GLOBALS['nag_shares']->listShares($user);
        if (is_a($shares, 'PEAR_Error')) {
            $hasError = true;
            Horde::logMessage($shares, __FILE__, __LINE__, PEAR_LOG_ERR);
        }
        foreach ($shares as $share) {
            $share->removeUser($user);
        }

        if ($hasError) {
            return PEAR::raiseError(sprintf(_("There was an error removing tasks for %s. Details have been logged."), $user));
        } else {
            return true;
        }
    }

}
