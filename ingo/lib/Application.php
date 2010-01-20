<?php
/**
 * Ingo application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Ingo
 */

/* Determine the base directories. */
if (!defined('INGO_BASE')) {
    define('INGO_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(INGO_BASE . '/config/horde.local.php')) {
        include INGO_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', INGO_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

/**
 * Ingo application API.
 *
 */
class Ingo_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $all_rulesets - TODO
     *   $ingo_shares - TODO
     *   $ingo_storage - TODO
     */
    protected function _init()
    {
        // Load the Ingo_Storage driver.
        $GLOBALS['ingo_storage'] = Ingo_Storage::factory();

        // Create the ingo session (if needed).
        if (!isset($_SESSION['ingo']) || !is_array($_SESSION['ingo'])) {
            Ingo_Session::createSession();
        }

        // Create shares if necessary.
        $driver = Ingo::getDriver();
        if ($driver->supportShares()) {
            $GLOBALS['ingo_shares'] = Horde_Share::singleton($registry->getApp());
            $GLOBALS['all_rulesets'] = Ingo::listRulesets();

            /* If personal share doesn't exist then create it. */
            $signature = $_SESSION['ingo']['backend']['id'] . ':' . Horde_Auth::getAuth();
            if (!$GLOBALS['ingo_shares']->exists($signature)) {
                $identity = Horde_Prefs_Identity::singleton();
                $name = $identity->getValue('fullname');
                if (trim($name) == '') {
                    $name = Horde_Auth::getOriginalAuth();
                }
                $share = $GLOBALS['ingo_shares']->newShare($signature);
                $share->set('name', $name);
                $GLOBALS['ingo_shares']->addShare($share);
                $GLOBALS['all_rulesets'][$signature] = $share;
            }

            /* Select current share. */
            $_SESSION['ingo']['current_share'] = Horde_Util::getFormData('ruleset', @$_SESSION['ingo']['current_share']);
            if (empty($_SESSION['ingo']['current_share']) ||
                empty($GLOBALS['all_rulesets'][$_SESSION['ingo']['current_share']]) ||
                !$GLOBALS['all_rulesets'][$_SESSION['ingo']['current_share']]->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ)) {
                $_SESSION['ingo']['current_share'] = $signature;
            }
        } else {
            $GLOBALS['ingo_shares'] = null;
        }
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        return array(
            'title' => array(
                'ingo:allow_rules' => _("Allow Rules"),
                'ingo:max_rules' => _("Maximum Number of Rules")
            ),
            'tree' => array(
                'ingo' => array(
                    'allow_rules' => false,
                    'max_rules' => false
                )
            ),
            'type' => array(
                'ingo:allow_rules' => 'boolean',
                'ingo:max_rules' => 'int'
            )
        );
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
            case 'allow_rules':
                $allowed = (bool)count(array_filter($allowed));
                break;

            case 'max_rules':
                $allowed = max($allowed);
                break;
            }
        }

        return $allowed;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Ingo::getMenu();
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

        /* Remove all filters/rules owned by the user. */
        $result = $GLOBALS['ingo_storage']->removeUserData($user);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Now remove all shares owned by the user. */
        if (!empty($GLOBALS['ingo_shares'])) {
            /* Get the user's default share. */
            $share = $GLOBALS['ingo_shares']->getShare($user);
            if (is_a($share, 'PEAR_Error')) {
                Horde::logMessage($share, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $share;
            } else {
                $result = $GLOBALS['ingo_shares']->removeShare($share);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $result;
                }
            }

            /* Get a list of all shares this user has perms to and remove the
             * perms. */
            $shares = $GLOBALS['ingo_shares']->listShares($user);
            if (is_a($shares, 'PEAR_Error')) {
                Horde::logMessage($shares, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
            foreach ($shares as $share) {
                $share->removeUser($user);
            }

            /* Get a list of all shares this user owns and has perms to delete
             * and remove them. */
            $shares = $GLOBALS['ingo_shares']->listShares($user, Horde_Perms::DELETE, $user);
            if (is_a($shares, 'PEAR_Error')) {
                Horde::logMessage($shares, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $shares;
            }
            foreach ($shares as $share) {
                $GLOBALS['ingo_shares']->removeShare($share);
            }
        }

        return true;
    }

}
