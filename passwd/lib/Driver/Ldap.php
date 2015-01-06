<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * The LDAP class attempts to change a user's password stored in an LDAP
 * directory service.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @author    Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author    Jan Schneider <jan@horde.org>
 * @author    Tjeerd van der Zee <admin@xar.nl>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Ldap extends Passwd_Driver
{
    /**
     * LDAP object.
     *
     * @var resource
     */
    protected $_ldap = false;

    /**
     * The user's DN.
     *
     * @var string
     */
    protected $_userdn;

    /**
     */
    public function __construct(array $params = array())
    {
        foreach (array('basedn', 'ldap', 'uid') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException(__CLASS__ . ': Missing ' . $val . ' parameter.');
            }
        }

        $this->_ldap = $params['ldap'];
        unset($params['ldap']);

        parent::__construct(array_merge(array(
            'host' => 'localhost',
            'port' => 389,
            'encryption' => 'crypt',
            'show_encryption' => 'true',
            'uid' => 'uid',
            'basedn' => '',
            'admindn' => '',
            'adminpw' => '',
            'realm' => '',
            'filter' => null,
            'tls' => false,
            'attribute' => 'userPassword',
            'shadowlastchange' => '',
            'shadowmin' => ''
        ), $params));

        if (!empty($this->_params['tls']) &&
            empty($this->_params['sslhost'])) {
            $this->_params['sslhost'] = $this->_params['host'];
        }
    }

    /**
     * Compares a plaintext password with an encrypted password.
     *
     * @param string $encrypted  An encrypted password.
     * @param string $plaintext  An unencrypted password.
     *
     * @throws Passwd_Exception if passwords don't match.
     */
    protected function _comparePasswords($encrypted, $plaintext)
    {
        $encrypted = preg_replace('/^{MD5}(.*)/i', '{MD5-BASE64}$1', $encrypted);
        return parent::_comparePasswords($encrypted, $plaintext);
    }

    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        global $injector;

        // Append realm as username@realm if 'realm' parameter is set.
        if (!empty($this->_params['realm'])) {
            $user .= '@' . $this->_params['realm'];
        }

        // Try to get the user's dn from config.
        if (isset($this->_params['userdn'])) {
            $this->_userdn = str_replace('%u', $user, $this->_params['userdn']);
        } else {
            try {
                $this->_userdn = $injector->getInstance('Horde_Core_Hooks')->callHook(
                    'userdn',
                    'passwd',
                    array($user)
                );
            } catch (Horde_Exception_HookNotSet $e) {
                // @todo Fix finding the user DN.
                // $this->_userdn = $this->_ldap->findUserDN($user);
                $this->_userdn = $this->_params['uid'] . '=' . $user . ',' . $this->_params['basedn'];
            }
        }

        try {
            // Check the old password by binding as the userdn.
            $this->_ldap->bind($this->_userdn, $oldpass);
        } catch (Horde_Ldap_Exception $e) {
            throw new Passwd_Exception($e);
        }

        // Rebind with admin credentials.
        if (!empty($this->_params['admindn'])) {
            try {
                $this->_ldap->bind();
            } catch (Horde_Ldap_Exception $e) {
                throw new Passwd_Exception($e);
            }
        }

        // Get existing user information.
        try {
            if (!($entry = $this->_ldap->getEntry($this->_userdn))) {
                throw new Passwd_Exception(_("User not found."));
            }
        } catch (Horde_Ldap_Exception $e) {
            throw new Passwd_Exception($e);
        }

        // Init the shadow policy array.
        $lookupshadow = array(
            'shadowlastchange' => false,
            'shadowmin' => false
        );
        foreach (array_keys($lookupshadow) as $val) {
            if (!empty($this->_params[$val]) &&
                $entry->exists($this->_params[$val])) {
                $lookupshadow[$val] = $entry->getValue($this->_params[$val]);
            }
        }

        // Check if we may change the password.
        if ($lookupshadow['shadowlastchange'] &&
            $lookupshadow['shadowmin'] &&
            (($lookupshadow['shadowlastchange'] + $lookupshadow['shadowmin']) > (time() / 86400))) {
            throw new Passwd_Exception(_("Minimum password age has not yet expired"));
        }

        // Change the user's password and update lastchange.
        try {
            $entry->replace(array(
                $this->_params['attribute'] => $this->_encryptPassword($newpass)
            ), true);

            if (!empty($this->_params['shadowlastchange']) &&
                $lookupshadow['shadowlastchange']) {
                $entry->replace(array(
                    $this->_params['shadowlastchange'] => floor(time() / 86400)
                ));
            }

            $entry->update();
        } catch (Horde_Ldap_Exception $e) {
            throw new Passwd_Exception($e);
        }
    }

}
