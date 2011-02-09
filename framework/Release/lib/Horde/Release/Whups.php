<?php
/**
 * Class for interfacing with the tickets API.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Release
 */
class Horde_Release_Whups
{
    /**
     * Instance of Horde_Rpc client object.
     *
     * @var Horde_Rpc
     */
    protected $_client;

    /**
     * Local copy of config params.
     *
     * @var array
     */
    protected $_params;

    /**
     * Http client
     *
     * @TODO: inject this
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
        $this->_params = $params;
        $options = array('user' => $this->_params['user'],
                         'pass' => $this->_params['pass']);
        $this->_http = new Horde_Http_Client($options);
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
            $res = Horde_Rpc::request('jsonrpc', $this->_params['url'], $method, $this->_http, $params);
        } catch (Horde_Http_Client_Exception $e) {
            throw new Horde_Exception_Prior($e);
        }
    }

    /**
     * Look up the queue id for the requested module name.
     *
     * @param string $module  TODO
     *
     * @return boolean  TODO
     */
    function getQueueId($module)
    {
        if ($module == 'horde') {
            $module = 'horde base';
        }

        $queues = $this->_listQueues();

        foreach ($queues as $id => $queue) {
            if (strtolower($queue) == $module) {
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
                                  'tickets.listQueues',
                                  $this->_http,
                                  null)->result;
    }

}
