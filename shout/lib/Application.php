<?php
/**
 * Shout application interface.
 *
 * This file defines Shout's application interface.
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
    public $version = 'H4 (1.0-git)';
    public $contexts = null;
    public $extensions = null;
    public $devices = null;

    public function __construct($args = array())
    {
        if (!empty($args['init'])) {
            $GLOBALS['registry'] = &Horde_Registry::singleton();
            $registry = &$GLOBALS['registry'];
            try {
                $registry->pushApp('shout', array('check_perms' => true,
                                                             'logintasks' => true));
            } catch (Horde_Exception $e) {
                Horde::authenticationFailure('shout', $e);
            }

            // Ensure Shout is properly configured before use
            $shout_configured = (@is_readable(SHOUT_BASE . '/config/conf.php'));
            if (!$shout_configured) {
                Horde_Test::configFilesMissing('Shout', SHOUT_BASE, array('conf.php'));
            }

            define('SHOUT_TEMPLATES', $registry->get('templates'));

            $this->contexts = Shout_Driver::factory('storage');
            $this->extensions = Shout_Driver::factory('extensions');
            $this->devices = Shout_Driver::factory('devices');

            try {
                $contexts = $this->contexts->getContexts();
            } catch (Shout_Exception $e) {
                $notification->push($e);
                $contexts = false;
            }

            $notification = Horde_Notification::singleton();
            $GLOBALS['notification'] = $notification;
            $notification->attach('status');

            if (count($contexts) == 1) {
                // Default to the user's only context
                if (!empty($context) && $context != $contexts[0]) {
                    $notification->push(_("You do not have permission to access that context."), 'horde.error');
                }
                $context = $contexts[0];
            } elseif (!empty($context) && !in_array($context, $contexts)) {
                $notification->push(_("You do not have permission to access that context."), 'horde.error');
                $context = false;
            } elseif (!empty($context)) {
                $notification->push("Please select a context to continue.", 'horde.info');
                $context = false;
            }

            $_SESSION['shout']['context'] = $context;
        }
    }

    public function perms()
    {
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }

        $perms['tree']['shout']['superadmin'] = false;
        $perms['title']['shout:superadmin'] = _("Super Administrator");

        if (empty($this->contexts)) {
            $this->__construct(array('init' => true));
        }

        $contexts = $this->contexts->getContexts();

        $perms['tree']['shout']['contexts'] = false;
        $perms['title']['shout:contexts'] = _("Contexts");

        // Run through every contact source.
        foreach ($contexts as $context) {
            $perms['tree']['shout']['contexts'][$context] = false;
            $perms['title']['shout:contexts:' . $context] = $context;

            foreach(
                array(
                    'extensions' => 'Extensions',
                    'devices' => 'Devices',
                    'conferences' => 'Conference Rooms',
                )
                as $module => $modname) {
                $perms['tree']['shout']['contexts'][$context][$module] = false;
                $perms['title']["shout:contexts:$context:$module"] = $modname;
            }
        }

        return $perms;
    }
}
