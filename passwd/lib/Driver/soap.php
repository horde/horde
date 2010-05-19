<?php
/**
 * The SOAP driver attempts to change a user's password through a SOAP
 * request.
 *
 * $Horde: passwd/lib/Driver/soap.php,v 1.1.2.1 2009/06/10 08:20:39 jan Exp $
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
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
    function Passwd_Driver_soap($params = array())
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
        parent::Passwd_Driver($params);
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function changePassword($username,  $old_password, $new_password)
    {
        if (!class_exists('SoapClient')) {
            return PEAR::raiseError('You need the soap PHP extension to use this driver.');
        }
        if (empty($this->_params['wsdl']) &&
            (empty($this->_params['soap_params']['location']) ||
             empty($this->_params['soap_params']['uri']))) {
            return PEAR::raiseError('Either the "wsdl" or the "location" and "uri" parameter must be provided.');
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
            return PEAR::raiseError($result->getMessage(), $result->getCode());
        }

        return true;
    }

}
