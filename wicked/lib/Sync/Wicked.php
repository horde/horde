<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Wicked
 */

/**
 * Wicked_Driver:: defines an API for implementing storage backends for
 * Wicked.
 *
 * @author  Duck <duck@obala.net>
 * @package Wicked
 */
class Wicked_Sync_Wicked extends Wicked_Sync
{
    /**
     *
     * @var Horde_Http_Client
     */
    protected $_client;

    public function __construct(array $params = array())
    {
        parent::__construct($params);
        $this->_client = $GLOBALS['injector']->
            getInstance('Horde_Core_Factory_HttpClient')->
            create(array('user' => $this->_params['user'],
                         'pass' => $this->_params['password'])
        );
    }

    /**
     * Returns a list of available pages.
     *
     * @return array  An array of all available pages.
     */
    public function listPages()
    {
        return $this->_getData('list');
    }

    /**
     * Get the wiki source of a page specified by its name.
     *
     * @param string $name  The name of the page to fetch
     *
     * @return string  Page data.
     * @throws Wicked_Exception
     */
    public function getPageSource($pageName)
    {
        return $this->_getData('getPageSource', array($pageName));
    }

    /**
     * Return basic page information.
     *
     * @param string $pageName Page name
     *
     * @return array  Page data.
     * @throws Wicked_Exception
     */
    public function getPageInfo($pageName)
    {
        return $this->_getData('getPageInfo', array($pageName));
    }

    /**
     * Return basic pages information.
     *
     * @param array $pages Page names to get info for
     *
     * @return array  Pages data.
     * @throws Wicked_Exception
     */
    public function getMultiplePageInfo($pages = array())
    {
        return $this->_getData('getMultiplePageInfo', array($pages));
    }

    /**
     * Return page history.
     *
     * @param string $pagename Page name
     *
     * @return array  An array of page parameters.
     */
    public function getPageHistory($pagename)
    {
        return $this->_getData('getPageHistory', array($pagename));
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
     * @throws Wicked_Exception
     */
    public function editPage($pagename, $text, $changelog = '', $minorchange = false)
    {
        $this->_getData('edit', array($pagename, $text, $changelog, $minorchange));
    }

    /**
     * Process remote call
     *
     * @param string $method Method name to call
     * @param array $params Array of parameters
     *
     * @return mixed
     * @throws Wicked_Exception
     */
    protected function _getData($method, array $params = array())
    {
        try {
            return Horde_Rpc::request(
                'xmlrpc',
                $this->_params['url'],
                $this->_params['prefix'] . '.' . $method,
                $this->_client,
                $params);
        } catch (Horde_Http_Client_Exception $e) {
            throw new Wicked_Exception($e);
        }
    }

}
