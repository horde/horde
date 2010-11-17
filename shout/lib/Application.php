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
    public $vfs = null;

    /**
     * Initialization function.
     *
     * Global variables defined:
     */
    protected function _init()
    {
        try {
            $this->storage = Shout_Driver::factory('storage');
            $this->extensions = Shout_Driver::factory('extensions');
            $this->devices = Shout_Driver::factory('devices');
            $this->dialplan = Shout_Driver::factory('dialplan');
            $conf = $GLOBALS['conf'];
            $this->vfs = VFS::singleton($conf['ivr']['driver'], $conf['ivr']['params']);

            $accounts = $this->storage->getAccounts();
        } catch (Shout_Exception $e) {
            $GLOBALS['notification']->push($e);
            $accounts = false;
            return false;
        }

        $account = Horde_Util::getFormData('account');
        if (empty($account)) {
            $account = $GLOBALS['session']->get('shout', 'curaccount_code');
        }

        if (!empty($account) && !in_array($account, array_keys($accounts))) {
            // Requested account not available
            $GLOBALS['notification']->push(_("You do not have permission to access that account."), 'horde.error');
            $account = false;
        }

        if (empty($account)) {
            if (count($accounts)) {
                // Default to the user's first account
                $account = reset(array_keys($accounts));
            } else {
                // No account requested and/or no accounts available anyway
                $GLOBALS['notification']->push("Please select a account to continue.", 'horde.info');
                $account = false;
            }
        }

        $session->set('shout', 'curaccount_code', $accounts[$account]['code']);
        $session->set('shout', 'curaccount_name', $accounts[$account]['name']);
    }

    /**
     * TODO
     */
    public function perms()
    {
        $perms = array(
            'accounts' => array(
                'title' => _("Accounts")
            ),
            'superadmin' => array(
                'title' => _("Super Administrator")
            )
        );

        $accounts = $this->storage->getAccounts();

        // Run through every contact source.
        foreach ($accounts as $code => $info) {
            $perms['account:' . $code] = array(
                'title' => $info['name']
            );

            foreach(
                array(
                    'extensions' => 'Extensions',
                    'devices' => 'Devices',
                    'conferences' => 'Conference Rooms',
                )
                as $module => $modname) {
                $perms['accounts:' . $code . ':' . $module] = array(
                    'title' => $modname
                );
            }
        }

        return $perms;
    }


    public function getRecordings()
    {
        $account = $GLOBALS['session']->get('shout', 'curaccount_code');
        $rlist = $this->vfs->listFolder($account);

        // In Asterisk, filenames the same basename and different extension are
        // functionally equivalent.  Asterisk chooses the file based on the least cost
        // to transcode.  For that reason, we will drop the filename extension when
        // handling files.
        $recordings = array();
        foreach ($rlist as $name => $info) {
            $name = substr($name, 0, strrpos($name, '.'));
            $info['name'] = $name;
            $recordings[$name] = $info;
        }

        return $recordings;
    }

}
