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
        $GLOBALS['registry']->setTimeZone();

        // Create a share instance.
        $GLOBALS['nag_shares'] = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();

        Nag::initialize();
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
        case 'max_tasks':
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
        global $prefs, $registry;

        switch ($ui->group) {
        case 'share':
            if (!$prefs->isLocked('default_tasklist')) {
                $all_tasklists = Nag::listTasklists();
                $tasklists = array();

                foreach ($all_tasklists as $id => $tasklist) {
                    if (!empty($conf['share']['hidden']) &&
                        ($tasklist->get('owner') != $GLOBALS['registry']->getAuth()) &&
                        !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
                        continue;
                    }
                    $tasklists[$id] = $tasklist;
                }

                $vals = array();
                foreach ($tasklists as $id => $tasklist) {
                    $vals[htmlspecialchars($id)] = htmlspecialchars($tasklist->get('name'));
                }
                $ui->override['default_tasklist'] = $vals;
            }
            break;

        case 'tasks':
            if (!$prefs->isLocked('default_due_time')) {
                $twentyfour = $prefs->getValue('twentyFour');

                $vals = array('now' => _("The current hour"));
                for ($i = 0; $i < 24; ++$i) {
                    $value = sprintf('%02d:00', $i);
                    $vals[$value] = ($twentyfour)
                        ? $value
                        : sprintf('%02d:00 ' . ($i >= 12 ? _("pm") : _("am")), ($i % 12 ? $i % 12 : 12));
                }
                $ui->override['default_due_time'] = $vals;
            }
            break;
        }

        $show_external = array();
        if ($registry->hasMethod('getListTypes', 'whups')) {
            $show_external['whups'] = $registry->get('name', 'whups');
        }
        if (count($show_external)) {
            $ui->override['show_external'] = $show_external;
        } else {
            $ui->suppress[] = 'show_external';
            $ui->suppressGroups[] = 'external';
        }
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $conf, $prefs;

        switch ($ui->group) {
        case 'notification':
            if (empty($conf['alarms']['driver']) ||
                $prefs->isLocked('task_alarms') ||
                $prefs->isLocked('task_alarms_select')) {
                $ui->suppress[] = 'task_alarms';
            }
            break;
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
        case 'task_alarms_select':
            return Horde_Core_Prefs_Ui_Widgets::alarm(array(
                'label' => _("Choose how you want to receive reminders for tasks with alarms:"),
                'pref' => 'task_alarms'
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
        case 'task_alarms_select':
            $data = Horde_Core_Prefs_Ui_Widgets::alarmUpdate($ui, array('pref' => 'task_alarms'));
            if (!is_null($data)) {
                $GLOBALS['prefs']->setValue('task_alarms', serialize($data));
                return true;
            }
            break;
        }

        return false;
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
     * @throws Nag_Exception
     */
    public function removeUserData($user)
    {
        /* Get the share for later deletion */
        try {
            $share = $GLOBALS['nag_shares']->getShare($user);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        /* Get the list of all tasks */
        $tasks = Nag::listTasks(null, null, null, $user, 1);
        if ($tasks instanceof PEAR_Error) {
            Horde::logMessage($tasks, 'ERR');
            throw new Nag_Exception(sprintf(_("There was an error removing tasks for %s. Details have been logged."), $user));
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

        /* ...and finally, delete the actual share */
        if (!empty($share)) {
            try {
                $GLOBALS['nag_shares']->removeShare($share);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Nag_Exception(sprintf(_("There was an error removing tasks for %s. Details have been logged."), $user));
            }
        }

        /* Now remove perms for this user from all other shares */
        try {
            $shares = $GLOBALS['nag_shares']->listShares($user);
            foreach ($shares as $share) {
               $share->removeUser($user);
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Nag_Exception(sprintf(_("There was an error removing tasks for %s. Details have been logged."), $user));
        }
    }

}
