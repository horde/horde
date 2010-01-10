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
if (!defined('OPERATOR_BASE')) {
    define('OPERATOR_BASE', dirname(__FILE__). '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(OPERATOR_BASE. '/config/horde.local.php')) {
        include OPERATOR_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', OPERATOR_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Operator_Application extends Horde_Registry_Application
{
    public $version = 'H4 (1.0-git)';
    public $driver = null;

    public function __construct($args = array())
    {
        if (!empty($args['init'])) {
            // Registry.
            $GLOBALS['registry'] = Horde_Registry::singleton();
            $registry = &$GLOBALS['registry'];

            try {
                $registry->pushApp('operator', !defined('AUTH_HANDLER'));
            } catch (Horde_Exception $e) {
                if ($e->getCode() == 'permission_denied') {
                    Horde::authenticationFailureRedirect();
                }
                Horde::fatal($e, __FILE__, __LINE__, false);
            }
            $conf = &$GLOBALS['conf'];
            @define('OPERATOR_TEMPLATES', $registry->get('templates'));

            // Notification system.
            $GLOBALS['notification'] = &Horde_Notification::singleton();
            $notification = &$GLOBALS['notification'];
            $notification->attach('status');

            // Define the base file path of Operator.
            @define('OPERATOR_BASE', dirname(__FILE__) . '/..');

            // Operator base library
            require_once OPERATOR_BASE . '/lib/Operator.php';

            // Operator backend.
            require_once OPERATOR_BASE . '/lib/Driver.php';
            $this->driver = Operator_Driver::factory();

            // Caching system for storing DB results
            $GLOBALS['cache'] = &Horde_Cache::singleton($conf['cache']['driver'],
                                    Horde::getDriverConfig('cache', $conf['cache']['driver']));

            // Start output compression.
            Horde::compressOutput();
        }
    }

    public function perms()
    {
        static $perms = array();

        if (!empty($perms)) {
            return $perms;
        }

        $perms['tree']['operator']['accountcodes'] = false;
        $perms['title']['operator:accountcodes'] = _("Account Codes");

        $accountcodes = Operator::getAccountCodes();
        foreach ($accountcodes as $accountcode) {
            $perms['tree']['operator']['accountcodes'][$accountcode] = false;
            $perms['title']['operator:accountcodes:' . $accountcode] = $accountcode;
        }

        return $perms;
    }
}
