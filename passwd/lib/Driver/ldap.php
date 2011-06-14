<?php
/**
 * The LDAP class attempts to change a user's password stored in an LDAP
 * directory service.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
 *
 * See http://www.horde.org/licenses/gpl.php for license information (GPL).
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Ralf Lang <lang@b1-systems.de>
 * @package Passwd
 */
class Passwd_Driver_ldap extends Passwd_Driver {

    /**
     * LDAP object.
     *
     * @var resource
     */
    protected  $_ldap = false;

    /**
     * The user's DN.
     *
     * @var string
     */
    protected  $_userdn;

    /**
     * Constructs a new Passwd_Driver_ldap object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function __construct($params = array())
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
                  'filter' => '',
                  'tls' => false,
                  'attribute' => 'userPassword',
                  'shadowlastchange' => 'shadowLastChange',
                  'shadowmin' => 'shadowMin'),
            $params);

        if (!empty($this->_params['tls']) &&
            empty($this->_params['sslhost'])) {
            $this->_params['sslhost'] = $this->_params['host'];
        }
    }



    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean  True or PEAR_Error based on success of the change.
     */
    function changePassword($username, $old_password, $new_password)
    {

/* This is wrong. We want to check against the stored value, not against hordeauth.
   These drivers are supposed to work against any backends, even if they are not related to
   any active horde authentication scheme.*/

/*        // See if the old password matches before allowing the change
        if ($old_password !== Auth::getCredential('password')) {
            return PEAR::raiseError(_("Incorrect old password."));
        }
*/


        // Append realm as username@realm if 'realm' parameter is set.
        if (!empty($this->_params['realm'])) {
            $username .= '@' . $this->_params['realm'];
        }

        // Get the user's dn from hook or fall back to Horde_Ldap::findUserDN.
        try {
            $this->_userdn = Horde::callHook('userdn', array($username), 'passwd');
        } catch (Horde_Exception_HookNotSet $e) {
            $this->_userdn = $this->_ldap->findUserDN($username);
        }

        // check the old password by binding as the userdn
        $this->_ldap->bind($this->_userdn, $old_password);
        // rebind with admin credentials
        $this->_ldap->bind();

        // Get existing user information
        $Entry = $this->_ldap->search($this->_userdn, $this->_params['filter'])->shiftEntry();

         if (!$Entry) {
             return PEAR::raiseError(_("User not found."));
         }

        // Init the shadow policy array
        $lookupshadow = array('shadowlastchange' => false,
                              'shadowmin' => false);

        if (!empty($this->_params['shadowlastchange']) && $Entry->exists($this->_params['shadowlastchange'])) {
            $lookupshadow['shadowlastchange'] = $Entry->getValue($this->_params['shadowlastchange']);            
        }
        if (!empty($this->_params['shadowmin']) && $Entry->exists($this->_params['shadowmin'])) {
            $lookupshadow['shadowmin'] = $Entry->getValue($this->_params['shadowmin']);            
        }

        // Check if we may change the password
        if ($lookupshadow['shadowlastchange'] &&
            $lookupshadow['shadowmin'] &&
            ($lookupshadow['shadowlastchange'] + $lookupshadow['shadowmin'] > (time() / 86400))) {
            return PEAR::raiseError(_("Minimum password age has not yet expired"));
        }

        // Change the user's password and update lastchange

        try {
            $Entry->replace(array($this->params['attribute'] => $this->encryptPassword($new_password)));

            if (!empty($this->_params['shadowlastchange']) &&
                $lookupshadow['shadowlastchange']) {
                $Entry->replace(array($this->_params['shadowlastchange']] = floor(time() / 86400)));
            }

            $Entry->update();
        } catch (Horde_Ldap_Exception $e) {
                throw new Horde_Passwd_Exception($e);
        }

        return true;
    }

}