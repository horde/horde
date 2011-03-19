<?php
/**
 * The Horde_Imsp_Auth_plaintext class for IMSP authentication.
 *
 * Required parameters:<pre>
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
class Horde_Imsp_Auth_Plaintext extends Horde_Imsp_Auth
{
    /**
     * Private authentication function.  Provides actual
     * authentication code.
     *
     * @access private
     * @param  mixed  $params Hash of IMSP parameters.
     *
     * @return mixed  Horde_Imsp object connected to server if successful,
     *                PEAR_Error on failure.
     */
    protected function _authenticate(array $params)
    {
         $userId = $params['username'];
         $credentials = $params['password'];

        /* Start the command. */
        $this->_imsp->send('LOGIN ', true, false);

        /* Username as a {}? */
        if (preg_match(Horde_Imsp::MUST_USE_LITERAL, $userId)) {
            $biUser = sprintf('{%d}', strlen($userId));
            $result = $this->_imsp->send($biUser, false, true, true);
        }
        $this->_imsp->send($userId . ' ', false, false);

        /* Pass as {}? */
        if (preg_match(Horde_Imsp::MUST_USE_LITERAL, $credentials)) {
            $biPass = sprintf('{%d}', strlen($credentials));
            $this->_imsp->send($biPass, false, true, true);
        }
        $this->_imsp->send($credentials, false, true);
        $server_response = $this->_imsp->receive();
        if ($server_response != 'OK') {
            return false;
        }

        return true;
    }

    /**
     * Force a logout command to the imsp stream.
     *
     */
    public function logout()
    {
        $this->_imsp->logout();
    }

    /**
     * Return the driver type
     *
     * @return string the type of this IMSP_Auth driver
     */
     public function getDriverType()
     {
         return 'plaintext';
     }

}
