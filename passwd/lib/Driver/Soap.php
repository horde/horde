<?php
/**
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Changes a password through a SOAP request.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Soap extends Passwd_Driver
{
    /**
     */
    public function __construct(array $params = array())
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
     */
    protected function changePassword($user, $oldpass, $newpass)
    {
        $args = array();
        if (($pos = array_search('username', $this->_params['arguments'])) !== false) {
            $args[$pos] = $user;
        }
        if (($pos = array_search('oldpassword', $this->_params['arguments'])) !== false) {
            $args[$pos] = $oldpass;
        }
        if (($pos = array_search('newpassword', $this->_params['arguments'])) !== false) {
            $args[$pos] = $newpass;
        }

        $client = new SoapClient(
            $this->_params['wsdl'],
            $this->_params['soap_params']
        );
        $res = $client->__soapCall($this->_params['method'], $args);
        if ($res instanceof SoapFault) {
            throw new Passwd_Exception($res->getMessage(), $res->getCode());
        }
    }

}
