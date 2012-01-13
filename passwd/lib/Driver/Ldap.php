<?php
/**
 * The LDAP class attempts to change a user's password stored in an LDAP
 * directory service.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See http://www.horde.org/licenses/gpl.php for license information (GPL).
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Ralf Lang <lang@b1-systems.de>
 * @author  Jan Schneider <jan@horde.org>
 * @package Passwd
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
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws InvalidArgumentException
     */
    public function __construct($params = array())
    {
        foreach (array('basedn', 'ldap', 'uid') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException(__CLASS__ . ': Missing ' . $val . ' parameter.');
            }
        }

        $this->_ldap = $params['ldap'];
        unset($params['ldap']);

        $this->_params = array_merge(
            array('host' => 'localhost',
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
                  'shadowmin' => ''),
            $params);

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
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($username, $old_password, $new_password)
    {
        // Append realm as username@realm if 'realm' parameter is set.
        if (!empty($this->_params['realm'])) {
            $username .= '@' . $this->_params['realm'];
        }

        // Get the user's dn from hook or fall back to Horde_Ldap::findUserDN.
        try {
            $this->_userdn = Horde::callHook('userdn', array($username), 'passwd');
        } catch (Horde_Exception_HookNotSet $e) {
            // @todo Fix finding the user DN.
            // $this->_userdn = $this->_ldap->findUserDN($username);
            // workaround
            $this->_userdn = $this->_params['uid'] . '=' . $username . ',' . $this->_params['basedn'];
        }

        try {
            // Check the old password by binding as the userdn.
            $this->_ldap->bind($this->_userdn, $old_password);
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
        $entry = $this->_getUserEntry();
        if (!$entry) {
             throw new Passwd_Exception(_("User not found."));
        }

        // Init the shadow policy array.
        $lookupshadow = array('shadowlastchange' => false,
                              'shadowmin' => false);

        if (!empty($this->_params['shadowlastchange']) &&
            $entry->exists($this->_params['shadowlastchange'])) {
            $lookupshadow['shadowlastchange'] = $entry->getValue($this->_params['shadowlastchange']);            
        }
        if (!empty($this->_params['shadowmin']) &&
            $entry->exists($this->_params['shadowmin'])) {
            $lookupshadow['shadowmin'] = $entry->getValue($this->_params['shadowmin']);            
        }

        // Check if we may change the password.
        if ($lookupshadow['shadowlastchange'] &&
            $lookupshadow['shadowmin'] &&
            ($lookupshadow['shadowlastchange'] + $lookupshadow['shadowmin'] > (time() / 86400))) {
            throw new Passwd_Exception(_("Minimum password age has not yet expired"));
        }

        // Change the user's password and update lastchange.
        try {
            $entry->replace(array($this->_params['attribute'] => $this->_encryptPassword($new_password)), true);

            if (!empty($this->_params['shadowlastchange']) &&
                $lookupshadow['shadowlastchange']) {
                $entry->replace(array($this->_params['shadowlastchange'] => floor(time() / 86400)));
            }

            $entry->update();
        } catch (Horde_Ldap_Exception $e) {
            throw new Passwd_Exception($e);
        }
    }

    /**
     * Returns the LDAP entry for the user.
     *
     * @return Horde_Ldap_Entry  The user's LDAP entry if it exists.
     * @throws Passwd_Exception
     */
    protected function _getUserEntry()
    {
        try {
            return $this->_ldap->getEntry($this->_userdn);
        } catch (Horde_Ldap_Exception $e) {
            throw new Passwd_Exception($e);
        }
    }
}
