<?php
/**
 * The Horde_Imsp_Auth class abstract class for IMSP authentication.
 *
 * Required Parameters:<pre>
 *   'username'  Username to logon to IMSP server as.
 *   'password'  Password for current user.
 *   'server'    The hostname of the IMSP server.
 *   'port'      The port of the IMSP server.</pre>
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Imsp
 */
abstract class Horde_Imsp_Auth_Base
{
    protected $_params = array();

    /**
     * Class variable to hold the resulting Horde_Imsp object
     *
     * @var Horde_Imsp_Client_Base
     */
    protected $_imsp;

    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
    }

    /**
     * Attempts to login to IMSP server.
     *
     * @param Horde_Imsp_Client_Base $client  The Imsp client connection.
     * @param boolean $login                  Remain logged in after auth?
     *
     * @return boolean
     */
    public function authenticate(Horde_Imsp_Client_Base $client, $login = true)
    {
        $this->_imsp = $client;
        if(!$this->_authenticate()) {
            return false;
        }
        if (!$login) {
            $this->_imsp->logout();
        }

        return true;
    }

    /**
     * Private authentication function. Provides actual authentication code.
     *
     * @return boolean
     */
    abstract protected function _authenticate();

    /**
     * Returns the type of this driver.
     *
     *
     * @return string Type of IMSP_Auth driver instance
     */
    abstract public function getDriverType();

    /**
     * Force a logout from the underlying IMSP stream.
     *
     */
    abstract public function logout();
}
