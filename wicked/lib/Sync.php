<?php
/**
 * Wicked_Sync:: defines an API for implementing synchronization backends for
 * Wicked.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Wicked
 */
abstract class Wicked_Sync {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

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
    public function factory($driver = 'Wicked', $params = array())
    {
        $driver = Horde_String::ucfirst(basename($driver));
        $class = 'Wicked_Sync_' . $driver;

        if (!class_exists($class)) {
            return false;
        }

        if (empty($params['user'])) {
            $params['user'] = $GLOBALS['registry']->getAuth();
        }
        if (empty($params['password'])) {
            $params['password'] = $GLOBALS['registry']->getAuthCredential('password');
        }
        return new $class($params);
    }

    /**
     * Constructs a new Wicked driver object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Returns a list of available pages.
     *
     * @return array  An array of all available pages.
     * @throws Wicked_Exception
     */
    abstract function listPages();

    /**
     * Get the wiki source of a page specified by its name.
     *
     * @param string $name  The name of the page to fetch
     *
     * @return array  Page data.
     * @throws Wicked_Exception
     */
    abstract function getPageSource($pageName);

    /**
     * Return basic page information.
     *
     * @param string $pageName Page name
     *
     * @return array  Page data.
     * @throws Wicked_Exception
     */
    abstract function getPageInfo($pageName);

    /**
     * Return basic information of .multiple pages
     *
     * @param array $pages Page names to get info for
     *
     * @return array  Pages data.
     * @throws Wicked_Exception
     */
    abstract function getMultiplePageInfo($pages = array());

   /**
     * Return page history.
     *
     * @param string $pagename Page name
     *
     * @return array  An array of page parameters.
     * @throws Wicked_Exception
     */
    abstract function getPageHistory($pagename);

    /**
     * Updates content of a wiki page. If the page does not exist it is
     * created.
     *
     * @param string $pagename Page to edit
     * @param string $text Page content
     * @param string $changelog Description of the change
     * @param boolean $minorchange True if this is a minor change
     *
     * @throws Wicked_Exception
     */
    abstract function editPage($pagename, $text, $changelog = '', $minorchange = false);
}
