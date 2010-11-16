<?php
/**
 * Preference storage implementation for an IMSP server.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_Storage_Imsp extends Horde_Prefs_Storage_Base
{
    /**
     * Boolean indicating whether or not we're connected to the IMSP server.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Handle for the IMSP server connection.
     *
     * @var Net_IMSP
     */
    protected $_imsp;

    /**
     */
    public function get($scope_ob)
    {
        $this->_connect();

        $prefs = $this->_imsp->get($scope_ob->scope . '.*');
        if ($prefs instanceof PEAR_Error) {
            throw new Horde_Prefs_Exception($prefs);
        }

        foreach ($prefs as $name => $val) {
            $name = str_replace($scope_ob->scope . '.', '', $name);
            if ($val != '-') {
                $scope_ob->set($name, $val);
            }
        }

        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
        $this->_connect();

        /* Driver has no support for storing locked status. */
        foreach ($scope_ob->getDirty() as $name) {
            $value = $scope_ob->get($name);
            $result = $this->_imsp->set($scope_ob->scope . '.' . $name, $value ? $value : '-');
            if ($result instanceof PEAR_Error) {
                throw new Horde_Prefs_Exception($result);
            }
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        // TODO
    }

    /* Helper functions. */

    /**
     * Attempts to set up a connection to the IMSP server.
     *
     * @throws Horde_Prefs_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        $this->_params['username'] = preg_match('/(^.*)@/', $this->getUser(), $matches)
            ? $matches[1]
            : $this->getUser();
        $this->_params['password'] = $this->_opts['password'];

        if (isset($this->_params['socket'])) {
            $this->_params['socket'] = $params['socket'] . 'imsp_' . $this->_params['username'] . '.sck';
        }

        $this->_imsp = Net_IMSP::factory('Options', $this->_params);
        $result = $this->_imsp->init();
        if ($result instanceof PEAR_Error) {
            throw new Horde_Prefs_Exception($result);
        }

        $this->_connected = true;
    }

}
