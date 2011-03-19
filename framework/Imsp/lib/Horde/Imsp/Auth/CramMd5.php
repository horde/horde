<?php
/**
 * The Horde_Imsp_Auth_cram_md5 class for IMSP authentication.
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
class Horde_Imsp_Auth_CramMd5 extends Horde_Imsp_Auth
{
    /**
     * Private authentication function.  Provides actual authentication code.
     *
     * @param  mixed $params Hash of IMSP parameters.
     *
     * @return boolean
     */
    protected function _authenticate(array $params)
    {
        $userId = $params['username'];
        $credentials = $params['password'];
        $this->_imsp->send('AUTHENTICATE CRAM-MD5');

        /* Get response and decode it. */
        $server_response = $this->_imsp->receive();
        $server_response = base64_decode(trim(substr($server_response, 2)));

        /* Build and base64 encode the response to the challange. */
        $response_to_send = $userId . ' ' . $this->_hmac($credentials, $server_response);
        $command_string = base64_encode($response_to_send);

        /* Send the response. */
        $this->_imsp->send($command_string, false);
        $result = $this->_imsp->receive();

        if ($result != 'OK') {
            $this->_imsp->_logger->err('Login to IMSP host failed.');
            return false;
        }

        return true;
    }

    /**
     * RFC 2104 HMAC implementation.
     *
     * @access private
     * @param  string  $key    The HMAC key.
     * @param  string  $data   The data to hash with the key.
     *
     * @return string  The MD5 HMAC.
     */
    protected function _hmac($key, $data)
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac('md5', $data, $key);
        }

        /* Byte length for md5. */
        $b = 64;

        if (strlen($key) > $b) {
            $key = pack('H*', md5($key));
        }

        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;
        return md5($k_opad . pack('H*', md5($k_ipad . $data)));
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
         return 'cram_md5';
     }

}
