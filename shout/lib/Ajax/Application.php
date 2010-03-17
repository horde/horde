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
        $shout = Horde_Registry::appInit('shout');
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
        $shout = Horde_Registry::appInit('shout');
        $account = $_SESSION['shout']['curaccount'];
        try {
            // FIXME: Use Form?
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $shout->extensions->deleteDestination($account, $vars->extension, $vars->type, $vars->destination);

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
    public function getDestinations()
    {
        $vars = $this->_vars;
        $shout = Horde_Registry::appInit('shout');
        $account = $_SESSION['shout']['curaccount'];
        try {
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
    public function getMenuInfo()
    {
        try {
            $vars = $this->_vars;
            $shout = Horde_Registry::appInit('shout');
            $account = $_SESSION['shout']['curaccount'];
            $menus = $shout->storage->getMenus($account);
            $menu = $vars->menu;
            if (!isset($menus[$menu])) {
                Horde::logMessage("User requested a menu that does not exist.", 'ERR');
                //$GLOBALS['notification']->push(_("That menu does not exist."), 'horde.error');
                // FIXME notifications
                return false;
            }

            $data['meta'] = $menus[$menu];
            $data['actions'] = $shout->dialplan->getMenuActions($account, $menu);
            return $data;
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            die(var_dump($e));
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    /**
     * TODO
     */
    public function getMenuActions()
    {
        try {
            $vars = $this->_vars;
            $GLOBALS['shout'] = Horde_Registry::appInit('shout');
            $account = $_SESSION['shout']['curaccount'];
            $actions = Shout::getMenuActions($contex, $menu);
            return $actions;
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    public function getActionForm()
    {
        try {
            $vars = $this->_vars;
            if (!($action = $vars->get('action'))) {
                throw new Shout_Exception("Invalid action requested.");
            }
            $GLOBALS['shout'] = Horde_Registry::appInit('shout');
            $account = $_SESSION['shout']['curaccount'];
            $actions = Shout::getMenuActions();
            $action = $actions[$action];
            $form = new Horde_Form($vars, $action['description'], 'editActionForm');
            foreach($action['args'] as $name => $info) {
                $defaults = array(
                    'required' => true,
                    'readonly' => false,
                    'description' => null,
                    'params' => array()
                );
                $info = array_merge($defaults, $info);
                $form->addVariable($info['name'], $name, $info['type'],
                                   $info['required'], $info['readonly'],
                                   $info['description'], $info['params']);
            }
            $form->setButtons(_("Save"));

            $this->_responseType = 'html';

            // Catch and return the output from $form->renderActive()
            ob_start();
            $form->renderActive(null, null, 'javascript:saveAction()');
            $output = ob_get_flush();
            return $output;

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
            $actions = Shout::getMenuActions();
            if (!isset($actions[$action])) {
                throw new Shout_Exception('Invalid action requested.');
            }
            $args = array();
            foreach ($actions[$action]['args'] as $name => $info) {
                $args[$name] = $vars->get($name);
            }
            $shout->dialplan->saveMenuAction($account, $menu, $digit, $action, $args);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    public function getRecordings()
    {
        try {
            return Shout::getRecordings($_SESSION['shout']['curaccount']);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }
    }

    public function getRecordings()
    {
        try {
            return Shout::getRecordings($_SESSION['shout']['curaccount']);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }
    }

    public function responseType()
    {
        return $this->_responseType;
    }
}
