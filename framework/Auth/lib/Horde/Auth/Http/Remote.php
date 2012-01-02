<?php
/**
 * The Horde_Auth_Http_Remote class authenticates users against a remote
 * HTTP-Auth endpoint.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, http://www.horde.org/licenses/lgpl21
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Auth
 */
class Horde_Auth_Http_Remote extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'client' - (Horde_Http_Client) [REQUIRED] TODO
     * 'url' - (string) [REQUIRED] TODO
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['url']) || !isset($params['client'])) {
            throw new InvalidArgumentException();
        }

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId       The userId to check.
     * @param array  $credentials  An array of login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        $this->_params['client']->request->username = $userId;
        $this->_params['client']->request->password = $credentials;
        $this->_params['client']->request->authenticationScheme = Horde_Http::AUTH_BASIC;
        $response = $this->__params['client']->get($this->_params['url']);
        if ($response->code != 200) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
