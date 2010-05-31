<?php
/**
 * The Horde_Auth_Http_Remote class authenticates users against a remote
 * HTTP-Auth endpoint.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Http_Remote extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'proxy' - (array) TODO
     * 'url' - (string) [REQUIRED] TODO
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['url'])) {
            throw new InvalidArgumentException();
        }

        $params = array_merge(array(
            'proxy' => array()
        ), $params);

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
        $options = array_merge(array(
            'allowRedirects' => true,
            'method' => 'GET',
            'timeout' => 5
        ), $this->_params['proxy']);

        $request = new HTTP_Request($this->_params['url'], $options);
        $request->setBasicAuth($userId, $credentials['password']);

        $request->sendRequest();

        if ($request->getResponseCode() != 200) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
