<?php
/**
 * The Auth_Http_Remote class authenticates users against a remote
 * HTTP-Auth endpoint.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Auth
 */
class Horde_Auth_HttpRemote extends Horde_Auth_Driver
{
    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId       The userId to check.
     * @param array  $credentials  An array of login credentials.
     *
     * @throws Horde_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        $options = array(
            'allowRedirects' => true,
            'method' => 'GET',
            'timeout' => 5
        );

        if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
            $options = array_merge($options, $GLOBALS['conf']['http']['proxy']);
        }

        $request = new HTTP_Request($this->_params['url'], $options);
        $request->setBasicAuth($userId, $credentials['password']);

        $request->sendRequest();

        if ($request->getResponseCode() != 200) {
            throw new Horde_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
