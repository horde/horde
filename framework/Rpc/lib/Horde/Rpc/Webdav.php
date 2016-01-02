<?php
/**
 * Copyright 2010-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ben Klang <bklang@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Rpc
 */

/**
 * @author   Ben Klang <bklang@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Rpc
 */
class Horde_Rpc_Webdav extends Horde_Rpc
{
    /**
     * Do we need an authenticated user?
     *
     * @var boolean
     */
    protected $_requireAuthorization = false;

    /**
     * The server instance.
     *
     * @var Sabre\DAV\Server
     */
    protected $_server;

    /**
     * Constructor.
     *
     * @param Horde_Controller_Request_Http $request  The request object.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters.
     */
    public function __construct($request, $params = array())
    {
        parent::__construct($request, $params);
        $this->_server = $GLOBALS['injector']->getInstance('Horde_Dav_Server');
    }

    /**
     * Implemented in Sabre\DAV\Server.
     */
    public function getInput()
    {
        return '';
    }

    /**
     * Sends an RPC request to the server and returns the result.
     *
     * @param string  The raw request string.
     *
     * @return string  The XML encoded response from the server.
     */
    public function getResponse($request)
    {
        $this->_server->exec();
    }

    /**
     * Implemented in Sabre\DAV\Server.
     */
    public function sendOutput($output)
    {
    }
}
