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
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Core_Auth_Imsp extends Horde_Auth_Base
{

    /**
     *
     * @var Horde_Imsp_Client_Base
     */
    protected $_imsp;


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
        $this->_imsp = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imsp')->create(null, $this->_params);
        if (!$result = $this->_imsp->authenticate(false)) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
