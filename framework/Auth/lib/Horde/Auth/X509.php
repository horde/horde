<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Auth
 * @since 2.1.0
 */

/**
 * The Horde_Auth_X509 class provides an authentication driver for using X509
 * client certificates. Since X509 certificates do not provide the password,
 * if the server setup requires the use of per-user passwords, a callback
 * function may be passed to obtain it from.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Auth
 * @since 2.1.0
 */
class Horde_Auth_X509 extends Horde_Auth_Base
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'transparent' => true
    );

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     *  - password: (string) If available, the password to use for the session.
     *    DEFAULT: no password used.
     *  - username_field: (string) Name of the $_SERVER field that
     *    the username can be found in. DEFAULT: 'SSL_CLIENT_S_DN_EMAILADDRESS'.
     *  - certificate_field: (string) Name of the $_SERVER field that contains
     *    the full certificate. DEFAULT: 'SSL_CLIENT_CERT'
     *  - ignore_purpose: (boolean) If true, will ignore any usage restrictions
     *    on the presented client certificate. I.e., if openssl_x509_checkpurpose
     *    returns false, authentication may still proceed. DEFAULT: false - ONLY
     *    ENABLE THIS IF YOU KNOW WHY YOU ARE DOING SO.
     *  - filter: (array)  An array where the keys are field names and the
     *                     values are the values those certificate fields MUST
     *                     match to be considered valid. Keys in the format of
     *                     fieldone:fieldtwo will be taken as parent:child.
     *                     DEFAULT: no additionachecks applied.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'password' => false,
            'username_field' => 'SSL_CLIENT_S_DN_CN',
            'certificate_field' => 'SSL_CLIENT_CERT',
            'ignore_purpose' => true,
            'filter' => array()
        ), $params);

        parent::__construct($params);
    }

    /**
     * Not implemented.
     *
     * @param string $userId      The userID to check.
     * @param array $credentials  An array of login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        throw new Horde_Auth_Exception('Unsupported.');
    }

    /**
     * Automatic authentication: checks if the username is set in the
     * configured header.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    public function transparent()
    {
        if (!is_callable('openssl_x509_parse')) {
            throw new Horde_Auth_Exception('SSL not enabled on server.');
        }

        if (empty($_SERVER[$this->_params['username_field']]) ||
            empty($_SERVER[$this->_params['certificate_field']])) {
            return false;
        }

        // Valid for client auth?
        $cert = openssl_x509_read($_SERVER[$this->_params['certificate_field']]);
        if (!$this->_params['ignore_purpose'] &&
            !openssl_x509_checkpurpose($cert, X509_PURPOSE_SSL_CLIENT) &&
            !openssl_x509_checkpurpose($cert, X509_PURPOSE_ANY)) {
            return false;
        }

        $c_parsed = openssl_x509_parse($cert);
        foreach ($this->_params['filter'] as $key => $value) {
            $keys = explode(':', $key);
            $c = $c_parsed;
            foreach ($keys as $k) {
                $c = $c[$k];
            }
            if ($c != $value) {
                return false;
            }
        }

        // Handle any custom validation added by sub classes.
        if (!$this->_validate($cert)) {
            return false;
        }

        // Free resources.
        openssl_x509_free($cert);

        // Set credentials
        $this->setCredential('userId', $_SERVER[$this->_params['username_field']]);
        $cred = array('certificate_id' => $c_parsed['hash']);
        if (!empty($this->_params['password'])) {
            $cred['password'] = $this->_params['password'];
        }
        $this->setCredential('credentials', $cred);

        return true;
    }

    /**
     * Perform additional validation of certificate fields.
     *
     * @return boolean
     */
    protected function _validate($certificate)
    {
        return true;
    }

}
