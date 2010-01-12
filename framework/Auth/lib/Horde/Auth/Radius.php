<?php
/**
 * The Horde_Auth_Radius class provides a RADIUS implementation of the Horde
 * authentication system.
 *
 * This class requires the 'radius' PECL extension:
 *   http://pecl.php.net/package/radius
 *
 * On *nix-y machines, this extension can be installed as follows:
 * <pre>
 * pecl install radius
 * </pre>
 *
 * Then, edit your php.ini file and make sure the following line is present:
 * <pre>
 *   For Windows machines:  extension=php_radius.dll
 *   For all others:        extension=radius.so
 * </pre>
 *
 * Required parameters:
 * <pre>
 * 'host' - (string) The RADIUS host to use (IP address or fully qualified
 *          hostname).
 * 'method' - (string) The RADIUS method to use for validating the request.
 *            Either: 'PAP', 'CHAP_MD5', 'MSCHAPv1', or 'MSCHAPv2'.
 *            ** CURRENTLY, only 'PAP' is supported. **
 * 'secret' - (string) The RADIUS shared secret string for the host. The
 *            RADIUS protocol ignores all but the leading 128 bytes
 *            of the shared secret.
 * </pre>
 *
 * Optional parameters:
 * <pre>
 * 'nas' - (string) The RADIUS NAS identifier to use.
 *         DEFAULT: The value of $_SERVER['HTTP_HOST'] or, if not
 *                  defined, then 'localhost'.
 * 'port' - (integer) The port to use on the RADIUS server.
 *          DEFAULT: Whatever the local system identifies as the
 *                   'radius' UDP port
 * 'retries' - (integer) The maximum number of repeated requests to make
 *             before giving up.
 *             DEFAULT: 3
 * 'suffix' - (string) The domain name to add to unqualified user names.
 *             DEFAULT: NONE
 * 'timeout' - (integer) The timeout for receiving replies from the server (in
 *             seconds).
 *             DEFAULT: 3
 * </pre>
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Radius extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Horde_Auth_Exception
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (!Horde_Util::extensionExists('radius')) {
            throw new Horde_Auth_Exception('Horde_Auth_Radius:: requires the radius PECL extension to be loaded.');
        }

        /* A RADIUS host is required. */
        if (empty($this->_params['host'])) {
            throw new Horde_Auth_Exception('Horde_Auth_Radius:: requires a RADIUS host to connect to.');
        }

        /* A RADIUS secret string is required. */
        if (empty($this->_params['secret'])) {
            throw new Horde_Auth_Exception('Horde_Auth_Radius:: requires a RADIUS secret string.');
        }

        /* A RADIUS authentication method is required. */
        if (empty($this->_params['method'])) {
            throw new Horde_Auth_Exception('Horde_Auth_Radius:: requires a RADIUS authentication method.');
        }

        /* RADIUS NAS Identifier. */
        if (empty($this->_params['nas'])) {
            $this->_params['nas'] = isset($_SERVER['HTTP_HOST'])
                ? $_SERVER['HTTP_HOST']
                : 'localhost';
        }

        /* Suffix to add to unqualified user names. */
        if (empty($this->_params['suffix'])) {
            $this->_params['suffix'] = '';
        }

        /* The RADIUS port to use. */
        if (empty($this->_params['port'])) {
            $this->_params['port'] = 0;
        }

        /* Maximum number of retries. */
        if (empty($this->_params['retries'])) {
            $this->_params['retries'] = 3;
        }

        /* RADIUS timeout. */
        if (empty($this->_params['timeout'])) {
            $this->_params['timeout'] = 3;
        }
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
            throw new Horde_Auth_Exception(_("Password required for RADIUS authentication."));
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
            throw new Horde_Auth_Exception(_("Authentication rejected by RADIUS server."));

        default:
            throw new Horde_Auth_Exception(radius_strerror($res));
        }
    }

}
