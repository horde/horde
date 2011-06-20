<?php
/**
 * The SOAP driver attempts to change a user's password through a SOAP
 * request.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * WARNING: This driver has only formally been converted to Horde 4. 
 * No testing has been done. If this doesn't work, please file bugs at
 * bugs.horde.org
 * If you really need this to work reliably, think about sponsoring development
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Passwd
 */
class Passwd_Driver_soap extends Passwd_Driver {

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function __construct ($params = array())
    {
        if (isset($params['wsdl'])) {
            unset($params['soap_params']['location']);
            unset($params['soap_params']['uri']);
        }
        if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
            $params['soap_params']['proxy_host'] = $GLOBALS['conf']['http']['proxy']['proxy_host'];
            $params['soap_params']['proxy_port'] = $GLOBALS['conf']['http']['proxy']['proxy_port'];
            $params['soap_params']['proxy_login'] = $GLOBALS['conf']['http']['proxy']['proxy_user'];
            $params['soap_params']['proxy_password'] = $GLOBALS['conf']['http']['proxy']['proxy_pass'];
        }
        $params['soap_params']['encoding'] = NLS::getCharset();
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
     * @return mixed  True on success, throws Passwd_Exception on failure.
     */
    function changePassword($username,  $old_password, $new_password)
    {
        if (!class_exists('SoapClient')) {
            throw new Passwd_Exception('You need the soap PHP extension to use this driver.');
        }
        if (empty($this->_params['wsdl']) &&
            (empty($this->_params['soap_params']['location']) ||
             empty($this->_params['soap_params']['uri']))) {
            throw new Passwd_Exception('Either the "wsdl" or the "location" and "uri" parameter must be provided.');
        }

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
        if (is_a($result, 'SoapFault')) {
            throw new Passwd_Exception($result->getMessage(), $result->getCode());
        }

        return true;
    }

}
