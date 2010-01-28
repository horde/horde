<?php
/**
 * Whups application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Whups through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Whups
 */

/* Determine the base directories. */
if (!defined('WHUPS_BASE')) {
    define('WHUPS_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(WHUPS_BASE . '/config/horde.local.php')) {
        include WHUPS_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', WHUPS_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Whups_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H3 (2.0-git)';

    /**
     * Perms cache
     *
     * @var array
     */
    protected $_permsCache = array();

    /**
     * Whups initialization.
     *
     * Global variables defined:
     *   $whups_driver - The global Whups driver object.
     */
    protected function _init()
    {
        // TODO: Remove once they can be autoloaded
        require_once 'Horde/Group.php';
        require_once 'Horde/Form.php';
        require_once 'Horde/Form/Renderer.php';

        $GLOBALS['whups_driver'] = Whups_Driver::factory();
        $GLOBALS['whups_driver']->initialise();
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        if (!empty($this->_permsCache)) {
            return $this->_permsCache;
        }

        /* Available Whups permissions. */
        $perms['tree']['whups']['admin'] = false;
        $perms['title']['whups:admin'] = _("Administration");

        $perms['tree']['whups']['hiddenComments'] = false;
        $perms['title']['whups:hiddenComments'] = _("Hidden Comments");

        $perms['tree']['whups']['queues'] = array();
        $perms['title']['whups:queues'] = _("Queues");

        /* Loop through queues and add their titles. */
        $queues = $GLOBALS['whups_driver']->getQueues();
        foreach ($queues as $id => $name) {
            $perms['tree']['whups']['queues'][$id] = false;
            $perms['title']['whups:queues:' . $id] = $name;

            $entries = array(
                'assign' => _("Assign"),
                'requester' => _("Set Requester"),
                'update' => _("Update")
            );

            foreach ($entries as $key => $val) {
                $perms['tree']['whups']['queues'][$id][$key] = false;
                $perms['title']['whups:queues:' . $id . ':' . $key] = $val;
                $perms['type']['whups:queues:' . $id . ':' . $key] = 'boolean';
                $perms['params']['whups:queues:' . $id . ':' . $key] = array();
            }
        }

        $perms['tree']['whups']['replies'] = array();
        $perms['title']['whups:replies'] = _("Form Replies");

        /* Loop through type and replies and add their titles. */
        foreach ($GLOBALS['whups_driver']->getAllTypes() as $type_id => $type_name) {
            foreach ($GLOBALS['whups_driver']->getReplies($type_id) as $reply_id => $reply) {
                $perms['tree']['whups']['replies'][$reply_id] = false;
                $perms['title']['whups:replies:' . $reply_id] = $type_name . ': ' . $reply['reply_name'];
            }
        }

        $this->_permsCache = $perms;

        return $perms;
    }

}
