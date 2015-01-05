<?php
/**
 * Copyright 2004-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package   Core
 */

/**
 * This class provides basic authentication against an IMSP server.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package   Core
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
        // Need to create the Imsp socket here since it requires a user/password
        // to create, and we don't have one until this method.
        $this->_params['username'] = $userID;
        $this->_params['password'] = $credentials['password'];
        $this->_imsp = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imsp')->create(null, $this->_params);
        if (!$this->_imsp->authenticate(false)) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
