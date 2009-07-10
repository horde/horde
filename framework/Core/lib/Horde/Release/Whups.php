<?php
/**
 * Class for interfacing with the tickets API.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
class Horde_Release_Whups
{
    /**
     * Instance of XML_RPC_Client object
     *
     * @var XML_RPC_CLient
     */
    protected $_client;

    /**
     * Local copy of config params.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param array $params  TODO
     */
    public function __construct($params)
    {
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
        if ($module == 'horde') {
            $module = 'horde base';
        }

        $id = $this->getQueueId($module);
        if ($id === false) {
            throw new Horde_Exception('Unable to locate requested queue');
        }

        $method = 'tickets.addVersion';
        $params = array($id, $version, $desc);
        $options = array('user' => $this->_params['user'],
                         'pass' => $this->_params['pass']);

        $res = Horde_RPC::request('jsonrpc', $this->_params['url'], $method, $params, $options);
        if ($res instanceof PEAR_Error) {
            throw new Horde_Exception($res);
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
        $method = 'tickets.listQueues';
        $result = Horde_RPC::request('jsonrpc', $this->_params['url'], $method,
                                     null, array('user' => $this->_params['user'],
                                                 'pass' => $this->_params['pass']));
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result);
        }

        return $result->result;
    }

}
