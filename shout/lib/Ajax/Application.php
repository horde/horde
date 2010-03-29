<?php
/**
 * Defines the AJAX interface for Shout.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 */
class Shout_Ajax_Application extends Horde_Ajax_Application_Base
{
    protected $_responseType = 'json';
    /**
     * Returns a notification handler object to use to output any
     * notification messages triggered by the action.
     *
     * @return Horde_Notification_Handler_Base   The notification handler.
     */
    public function notificationHandler()
    {
        // FIXME: Create Shout notification handler
        //return $GLOBALS['kronolith_notify'];
        return null;
    }

    /**
     * TODO
     */
    public function addDestination()
    {
        $vars = $this->_vars;
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $account = $_SESSION['shout']['curaccount'];
        try {
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $shout->extensions->addDestination($account, $vars->extension, $vars->type, $vars->destination);

            return $shout->extensions->getExtensions($account);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    /**
     * TODO
     */
    public function deleteDestination()
    {
        $vars = $this->_vars;
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $account = $_SESSION['shout']['curaccount'];
        try {
            // FIXME: Use Form?
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $shout->extensions->deleteDestination($account, $vars->extension, $vars->type, $vars->destination);

            return $this->getDestinations();
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    /**
     * TODO
     */
    public function getDestinations()
    {
        try {
            $vars = $this->_vars;
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $account = $_SESSION['shout']['curaccount'];
            return $shout->extensions->getExtensions($account);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    public function getDevices()
    {
        try {
            $vars = $this->_vars;
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $account = $_SESSION['shout']['curaccount'];
            return $shout->devices->getDevices($account);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    /**
     * TODO
     */
    public function getMenus()
    {
        try {
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $account = $_SESSION['shout']['curaccount'];
            $menus = $shout->storage->getMenus($account);
            if (empty($menus)) {
                return false;
            }
            foreach ($menus as $menu => $info) {
                // Fill in the actions for each menu
                $menus[$menu]['actions'] = $shout->dialplan->getMenuActions($account, $menu);
            }
            return $menus;
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    public function deleteMenu()
    {
        try {
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $account = $_SESSION['shout']['curaccount'];
            $menu = $this->_vars->get('menu');
            if (empty($menu)) {
                throw new Shout_Exception('Must specify a menu to delete.');
            }
            $shout->dialplan->deleteMenu($account, $menu);
            return true;
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    public function getConferences()
    {
        try {
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $account = $_SESSION['shout']['curaccount'];
            return $shout->storage->getConferences($account);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    public function saveMenuInfo()
    {
        try {
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $account = $_SESSION['shout']['curaccount'];
            $vars = &$this->_vars;
            $info = array(
                'name' => $vars->get('name'),
                'oldname' => $vars->get('oldname'),
                'description' => $vars->get('description'),
                'recording_id' => $vars->get('recording_id')
            );
            return $shout->storage->saveMenuInfo($account, $info);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    public function saveAction()
    {
        try {
            $shout = $GLOBALS['shout'] = Horde_Registry::appInit('shout');
            $vars = $this->_vars;
            if (!($action = $vars->get('action'))) {
                throw new Shout_Exception("Invalid action requested.");
            }
            $account = $_SESSION['shout']['curaccount'];
            $digit = $vars->get('digit');
            $menu = $vars->get('menu');
            $action = $vars->get('action');

            if ($action == 'none') {
                // Remove the menu action and return
                $shout->dialplan->deleteMenuAction($account, $menu, $digit);
                return true;
            }

            $actions = Shout::getMenuActions();
            if (!isset($actions[$action])) {
                throw new Shout_Exception('Invalid action requested.');
            }
            $args = array();
            foreach ($actions[$action]['args'] as $name => $info) {
                $args[$name] = $vars->get($name);
            }
            $shout->dialplan->saveMenuAction($account, $menu, $digit, $action, $args);
            return true;
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    public function logException()
    {
        $vars = &$this->_vars;
        $filename = $vars->get('fileName');
        $message = $vars->get('message');
        $stack = $vars->get('stack');
        $log = sprintf('Client side error in %s: %s.  Stacktrace follows:\n%s',
                       $filename, $message, $stack);
        Horde::logMessage($log, 'ERR');
        return true;
    }

    public function responseType()
    {
        return $this->_responseType;
    }
}
