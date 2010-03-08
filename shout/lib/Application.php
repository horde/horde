<?php
/**
 * Shout application interface.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Shout through this API.
 *
 * Copyright 2006-2010 Alkaloid Networks (http://projects.alkaloid.net/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 */

if (!defined('SHOUT_BASE')) {
    define('SHOUT_BASE', dirname(__FILE__). '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(SHOUT_BASE. '/config/horde.local.php')) {
        include SHOUT_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', SHOUT_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Shout_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (1.0-git)';

    /**
     * TODO
     */
    public $storage = null;

    /**
     * TODO
     */
    public $extensions = null;

    /**
     * TODO
     */
    public $devices = null;

    /**
     * TODO
     */
    public $dialplan = null;

    /**
     * TODO
     */
    static protected $_perms = array();

    /**
     * Initialization function.
     *
     * Global variables defined:
     */
    protected function _init()
    {
        $this->storage = Shout_Driver::factory('storage');
        $this->extensions = Shout_Driver::factory('extensions');
        $this->devices = Shout_Driver::factory('devices');
        $this->dialplan = Shout_Driver::factory('dialplan');

        try {
            $accounts = $this->storage->getAccounts();
        } catch (Shout_Exception $e) {
            $GLOBALS['notification']->push($e);
            $accounts = false;
        }

        $account = Horde_Util::getFormData('account');
        if (empty($account) && !empty($_SESSION['shout']['account'])) {
            $account = $_SESSION['shout']['account'];
        }

        if (!empty($account) && !in_array($account, $accounts)) {
            // Requested account not available
            $GLOBALS['notification']->push(_("You do not have permission to access that account."), 'horde.error');
            $account = false;
        }

        if (empty($account)) {
            if (count($accounts)) {
                // Default to the user's first account
                $account = reset($accounts);
            } else {
                // No account requested and/or no accounts available anyway
                $GLOBALS['notification']->push("Please select a account to continue.", 'horde.info');
                $account = false;
            }
        }

        $_SESSION['shout']['account'] = $account;
    }

    /**
     * TODO
     */
    public function perms()
    {
        if (!empty(self::$_perms)) {
            return self::$_perms;
        }

        self::$_perms['tree']['shout']['superadmin'] = false;
        self::$_perms['title']['shout:superadmin'] = _("Super Administrator");

        if (empty($this->accounts)) {
            $this->__construct(array('init' => true));
        }

        $accounts = $this->accounts->getAccounts();

        self::$_perms['tree']['shout']['accounts'] = false;
        self::$_perms['title']['shout:accounts'] = _("Accounts");

        // Run through every contact source.
        foreach ($accounts as $account) {
            self::$_perms['tree']['shout']['accounts'][$account] = false;
            self::$_perms['title']['shout:accounts:' . $account] = $account;

            foreach(
                array(
                    'extensions' => 'Extensions',
                    'devices' => 'Devices',
                    'conferences' => 'Conference Rooms',
                )
                as $module => $modname) {
                self::$_perms['tree']['shout']['accounts'][$account][$module] = false;
                self::$_perms['title']["shout:accounts:$account:$module"] = $modname;
            }
        }

        return self::$_perms;
    }

}
