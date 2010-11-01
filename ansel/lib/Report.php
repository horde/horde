<?php
/**
 * Reporting abstraction class
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Report {

    var $_title = '';

    /**
     * Create instance
     */
    function factory($driver = null, $params = array())
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['report_content']['driver'];
        }

        if (empty($params)) {
            $params = $GLOBALS['conf']['report_content'];
        }

        $class_name = 'Ansel_Report_' . $driver;
        if (!class_exists($class_name)) {
            return PEAR::RaiseError(_("Report driver does not exist."));
        }

        $report = new $class_name($params);

        return $report;
    }

    /**
     * Get reporting user email
     */
    function getUserEmail()
    {
        return $this->_getUserEmail();
    }

    /**
     * Get user email
     */
    function _getUserEmail($user = null)
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user)->getValue('from_addr');
    }

    /**
     * Get scope admins
     */
    function getAdmins()
    {
        $name = $GLOBALS['registry']->getApp() . ':admin';

        if ($GLOBALS['injector']->getInstance('Horde_Perms')->exists($name)) {
            return array();
        }

        $permission = $GLOBALS['injector']->getInstance('Horde_Perms')->getPermission($name);

        return $permission->getUserPermissions(PERM_DELETE);
    }

    /**
     * Set title
     */
    function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * Get report message title
     */
    function getTitle()
    {
        if (empty($this->_title)) {
            return sprintf(_("Content abuse report in %s"), $GLOBALS['registry']->get('name'));
        } else {
            return $this->_title;
        }
    }

    /**
     * Get report message content
     */
    function getMessage($message)
    {
        $message .=  "\n\n" . _("Report by user") . ': ' . $GLOBALS['registry']->getAuth()
                 . ' (' . $_SERVER['REMOTE_ADDR'] . ')';

        return $message;
    }

    /**
     * Report
     *
     * @param string $message to pass
     */
    function report($message, $users = array())
    {
        return PEAR::raiseError(_("Unsupported"));
    }
}
