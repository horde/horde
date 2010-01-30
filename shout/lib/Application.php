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
    public $contexts = null;

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
    static protected $_perms = array();

    /**
     * Initialization function.
     *
     * Global variables defined:
     */
    protected function _init()
    {
        $this->contexts = Shout_Driver::factory('storage');
        $this->extensions = Shout_Driver::factory('extensions');
        $this->devices = Shout_Driver::factory('devices');

        try {
            $contexts = $this->contexts->getContexts();
        } catch (Shout_Exception $e) {
            $GLOBALS['notification']->push($e);
            $contexts = false;
        }

        $context = Horde_Util::getFormData('context');
        if (!empty($context) && !in_array($context, $contexts)) {
            // Requested context not available
            $GLOBALS['notification']->push(_("You do not have permission to access that context."), 'horde.error');
            $context = false;
        }

        if (empty($context)) {
            if (count($contexts)) {
                // Default to the user's first context
                $context = reset($contexts);
            } else {
                // No context requested and/or no contexts available anyway
                $GLOBALS['notification']->push("Please select a context to continue.", 'horde.info');
                $context = false;
            }
        }

        $_SESSION['shout']['context'] = $context;
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

        if (empty($this->contexts)) {
            $this->__construct(array('init' => true));
        }

        $contexts = $this->contexts->getContexts();

        self::$_perms['tree']['shout']['contexts'] = false;
        self::$_perms['title']['shout:contexts'] = _("Contexts");

        // Run through every contact source.
        foreach ($contexts as $context) {
            self::$_perms['tree']['shout']['contexts'][$context] = false;
            self::$_perms['title']['shout:contexts:' . $context] = $context;

            foreach(
                array(
                    'extensions' => 'Extensions',
                    'devices' => 'Devices',
                    'conferences' => 'Conference Rooms',
                )
                as $module => $modname) {
                self::$_perms['tree']['shout']['contexts'][$context][$module] = false;
                self::$_perms['title']["shout:contexts:$context:$module"] = $modname;
            }
        }

        return self::$_perms;
    }

}
