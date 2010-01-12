<?php
/**
 * Wicked_Sync:: defines an API for implementing synchronization backends for
 * Wicked.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Wicked
 */
class Wicked_Sync {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Attempts to return a concrete Wicked_Sync instance based on $driver.
     *
     * @param string $driver  The type of the concrete Wicked_Sync subclass
     *                        to return.  The class name is based on the
     *                        sync driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Wicked_Sync    The newly created concrete Wicked_Sync
     *                        instance, or false on an error.
     */
    function factory($driver = 'wicked', $params = array())
    {
        $class = 'Wicked_Sync_' . $driver;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Sync/' . $driver . '.php';
        }

        if (empty($params['user'])) {
            $params['user'] = Horde_Auth::getAuth();
        }

        if (empty($params['password'])) {
            $params['password'] = Horde_Auth::getCredential('password');
        }

        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }

    /**
     * Constructs a new Wicked driver object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Wicked_Sync($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Returns a list of available pages.
     *
     * @return array  An array of all available pages.
     */
    function listPages()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Get the wiki source of a page specified by its name.
     *
     * @param string $name  The name of the page to fetch
     *
     * @return mixed        Array of page data on success; PEAR_Error on failure
     */
    function getPageSource($pageName)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Return basic page information.
     *
     * @param string $pageName Page name
     *
     * @return mixed        Array of page data on success; PEAR_Error on failure
     */
    function getPageInfo($pageName)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Return basic information of .multiple pages
     *
     * @param array $pages Page names to get info for
     *
     * @return mixed        Array of pages data on success; PEAR_Error on failure
     */
    function getMultiplePageInfo($pages = array())
    {
        return PEAR::raiseError(_("Unsupported"));
    }

   /**
     * Return page history.
     *
     * @param string $pagename Page name
     *
     * @return array  An array of page parameters.
     */
    function getPageHistory($pagename)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Updates content of a wiki page. If the page does not exist it is
     * created.
     *
     * @param string $pagename Page to edit
     * @param string $text Page content
     * @param string $changelog Description of the change
     * @param boolean $minorchange True if this is a minor change
     *
     * @return boolean | PEAR_Error True on success, PEAR_Error on failure.
     */
    function editPage($pagename, $text, $changelog = '', $minorchange = false)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

}
