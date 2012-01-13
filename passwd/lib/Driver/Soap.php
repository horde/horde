<?php
/**
 * The SOAP driver attempts to change a user's password through a SOAP
 * request.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Passwd
 */
class Passwd_Driver_Soap extends Passwd_Driver
{
    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Passwd_Exception
     */
    public function __construct($params = array())
    {
        if (!class_exists('SoapClient')) {
            throw new Passwd_Exception('You need the soap PHP extension to use this driver.');
        }

        if (empty($params['wsdl']) &&
            (empty($params['soap_params']['location']) ||
             empty($params['soap_params']['uri']))) {
            throw new Passwd_Exception('Either the "wsdl" or the "location" and "uri" parameter must be provided.');
        }

        if (isset($params['wsdl'])) {
            unset($params['soap_params']['location']);
            unset($params['soap_params']['uri']);
        }
        $params['soap_params']['exceptions'] = false;

        parent::__construct($params);
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($username, $old_password, $new_password)
    {
        $args = array();
        if (($pos = array_search('username', $this->_params['arguments'])) !== false) {
            $args[$pos] = $username;
        }
        if (($pos = array_search('oldpassword', $this->_params['arguments'])) !== false) {
            $args[$pos] = $old_password;
        }
        if (($pos = array_search('newpassword', $this->_params['arguments'])) !== false) {
            $args[$pos] = $new_password;
        }

        $client = new SoapClient($this->_params['wsdl'],
                                 $this->_params['soap_params']);
        $result = $client->__soapCall($this->_params['method'], $args);
        if ($result instanceof SoapFault) {
            throw new Passwd_Exception($result->getMessage(), $result->getCode());
        }
    }
}
