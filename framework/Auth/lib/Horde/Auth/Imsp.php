<?php
/**
 * The Horde_Auth_Imsp class provides basic authentication against an IMSP
 * server.
 * This will be most benificial if already using an IMSP based preference
 * system or IMSP based addressbook system
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Imsp extends Horde_Auth_Base
{
    /**
     * Private authentication function.
     *
     * @param string $userID      Username for IMSP server.
     * @param array $credentials  Hash containing 'password' element.
     *
     * @return boolean  True on success / False on failure.
     */
    protected function _authenticate($userID, $credentials)
    {
        $this->_params['username'] = $userID;
        $this->_params['password'] = $credentials['password'];

        $imsp = Net_IMSP_Auth::singleton($this->_params['auth_method']);
        if ($imsp instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($imsp->getMessage());
        }

        $result = $imsp->authenticate($this->_params, false);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
