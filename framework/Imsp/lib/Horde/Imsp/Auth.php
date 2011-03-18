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
abstract class Horde_Imsp_Auth
{
    /**
     * Class variable to hold the resulting Horde_Imsp object
     *
     * @var Horde_Imsp
     */
    protected $_imsp;

    /**
     * Attempts to login to IMSP server.
     *
     * @param array $params    Parameters for Horde_Imsp
     * @param boolean $login   Should we remain logged in after auth?
     *
     * @return mixed           Returns a Horde_Imsp object connected to
     *                         the IMSP server if login is true and
     *                         successful.  Returns boolean true if
     *                         successful and login is false.
     */
    public function authenticate(array $params, $login = true)
    {
        $this->_imsp = $this->_authenticate($params);
        if (!$login) {
            $this->_imsp->logout();
            return true;
        }

        return $this->_imsp;
    }

    /**
     * Private authentication function. Provides actual authentication code.
     *
     * @param  array   $params      Parameters for Horde_Imsp_Auth driver.
     *
     * @return mixed                Returns Horde_Imsp object connected to server
     *                              if successful, PEAR_Error on failure.
     */
    abstract protected function _authenticate(array $params);

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
