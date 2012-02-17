<?php
/**
 * Class for interfacing with the tickets API.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Release
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Release
 */

/**
 * Glue class for a modular CLI.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Release
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Release
 */
class Horde_Release_Whups
{
    /**
     * Local copy of config params.
     *
     * @var array
     */
    protected $_params;

    /**
     * Http client
     *
     * @var Horde_Http_Client
     */
    protected $_http;

    /**
     * Constructor.
     *
     * @param array $params  TODO
     */
    public function __construct($params)
    {
        if (isset($params['client'])) {
            $this->_http = $params['client'];
            unset($params['client']);
        } else {
            $this->_http = new Horde_Http_Client(
                array('request.username' => $params['user'],
                      'request.password' => $params['pass']));
        }
        $this->_params = $params;
    }

    /**
     * Add a new version to the current modules queue.
     *
     * @param string $module   The name of the module.
     * @param string $version  The version string.
     * @param string $desc     Descriptive text for this version.
     *
     * @throws Horde_Exception
     */
    public function addNewVersion($module, $version, $desc = '')
    {
        $id = $this->getQueueId($module);
        if ($id === false) {
            throw new Horde_Exception('Unable to locate requested queue');
        }

        $method = 'tickets.addVersion';
        $params = array($id, $version, $desc);
        try {
            Horde_Rpc::request('jsonrpc', $this->_params['url'], $method, $this->_http, $params);
        } catch (Horde_Http_Client_Exception $e) {
            throw new Horde_Exception_Wrapped($e);
        }
    }

    /**
     * Look up the queue id for the requested module name.
     *
     * @param string $module  TODO
     *
     * @return boolean  TODO
     */
    public function getQueueId($module)
    {
        $queues = $this->_listQueues();

        foreach ($queues as $id => $queue) {
            if ($queue == $module) {
                return $id;
            }
        }

        return false;
    }

    /**
     * Perform a listQueue api call.
     *
     * @return string  TODO
     * @throws Horde_Exception
     */
    protected function _listQueues()
    {
        return Horde_Rpc::request('jsonrpc',
                                  $this->_params['url'],
                                  'tickets.listSlugs',
                                  $this->_http,
                                  null)->result;
    }

}
