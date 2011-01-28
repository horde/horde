<?php
/**
 * The Horde_Auth_Radius class provides a RADIUS implementation of the Horde
 * authentication system.
 *
 * This class requires the 'radius' PECL extension:
 *   http://pecl.php.net/package/radius
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Radius extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Connection parameters.
     * <pre>
     * 'host' - (string) [REQUIRED] The RADIUS host to use (IP address or
     *          fully qualified hostname).
     * 'method' - (string) [REQUIRED] The RADIUS method to use for validating
     *            the request.
     *            Either: 'PAP', 'CHAP_MD5', 'MSCHAPv1', or 'MSCHAPv2'.
     *            ** CURRENTLY, only 'PAP' is supported. **
     * 'nas' - (string) The RADIUS NAS identifier to use.
     *         DEFAULT: The value of $_SERVER['HTTP_HOST'] or, if not
     *                  defined, then 'localhost'.
     * 'port' - (integer) The port to use on the RADIUS server.
     *          DEFAULT: Whatever the local system identifies as the
     *                   'radius' UDP port
     * 'retries' - (integer) The maximum number of repeated requests to make
     *             before giving up.
     *             DEFAULT: 3
     * 'secret' - (string) [REQUIRED] The RADIUS shared secret string for the
     *            host. The RADIUS protocol ignores all but the leading 128
     *            bytes of the shared secret.
     * 'suffix' - (string) The domain name to add to unqualified user names.
     *             DEFAULT: NONE
     * 'timeout' - (integer) The timeout for receiving replies from the server
     *             (in seconds).
     *             DEFAULT: 3
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!Horde_Util::extensionExists('radius')) {
            throw new Horde_Auth_Exception(__CLASS__ . ': requires the radius PECL extension to be loaded.');
        }

        foreach (array('host', 'secret', 'method') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
            }
        }

        $params = array_merge(array(
            'nas' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),
            'port' => 0,
            'retries' => 3,
            'suffix' => '',
            'timeout' => 3
        ), $params);

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $username    The userId to check.
     * @param array $credentials  An array of login credentials.
     *                            For radius, this must contain a password
     *                            entry.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($username, $credentials)
    {
        /* Password is required. */
        if (!isset($credentials['password'])) {
            throw new Horde_Auth_Exception('Password required for RADIUS authentication.');
        }

        $res = radius_auth_open();
        radius_add_server($res, $this->_params['host'], $this->_params['port'], $this->_params['secret'], $this->_params['timeout'], $this->_params['retries']);
        radius_create_request($res, RADIUS_ACCESS_REQUEST);
        radius_put_attr($res, RADIUS_NAS_IDENTIFIER, $this->_params['nas']);
        radius_put_attr($res, RADIUS_NAS_PORT_TYPE, RADIUS_VIRTUAL);
        radius_put_attr($res, RADIUS_SERVICE_TYPE, RADIUS_FRAMED);
        radius_put_attr($res, RADIUS_FRAMED_PROTOCOL, RADIUS_PPP);
        radius_put_attr($res, RADIUS_CALLING_STATION_ID, isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '127.0.0.1');

        /* Insert username/password into request. */
        radius_put_attr($res, RADIUS_USER_NAME, $username);
        radius_put_attr($res, RADIUS_USER_PASSWORD, $credentials['password']);

        /* Send request. */
        $success = radius_send_request($res);

        switch ($success) {
        case RADIUS_ACCESS_ACCEPT:
            break;

        case RADIUS_ACCESS_REJECT:
            throw new Horde_Auth_Exception('Authentication rejected by RADIUS server.');

        default:
            throw new Horde_Auth_Exception(radius_strerror($res));
        }
    }

}
