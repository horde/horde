<?php
/**
 * Passwd_Driver:: defines an API for implementing password change systems for
 * Passwd.
 *
 * Copyright 2000-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @since   Passwd 2.1
 * @package Passwd
 */
class Passwd_Driver {

    /**
     * Hash containing configuration parameters.
     *
     * @var array
     */
    public $_params = array();

    /**
     * Error string that will be returned to the user if an error occurs.
     *
     * @var string
     */
    public $_errorstr;

    /**
     * Constructs a new Passwd_Driver object.
     *
     * @param $params   A hash containing connection parameters.
     */
    function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Compare a plaintext password with an encrypted password.
     *
     * @return mixed  True if they match
     */
    function comparePasswords($encrypted, $plaintext)
    {
        if (preg_match('/^{[^}]+}/', $encrypted, $match)) {
            $encrypted = substr($encrypted, strlen($match[0]));
            $encryption = strtolower(substr($match[0], 1, strlen($match[0]) - 2));
            if ($this->_params['driver'] == 'ldap' && $encryption == 'md5') {
                $encryption = 'md5-base64';
            }
        } else {
            $encryption = $this->_params['encryption'];
        }

        $hashed = Horde_Auth::getCryptedPassword($plaintext,
                                           $encrypted,
                                           $encryption,
                                           false);

        if ($this->_params['show_encryption']) {
            /* Convert the hashing algorithm in both strings to uppercase. */
            $encrypted = preg_replace('/^({.*?})/e', "String::upper('\\1')", $encrypted);
            $hashed = preg_replace('/^({.*?})/e', "String::upper('\\1')", $hashed);
        }

        if  ($encrypted == $hashed) {
            return true;
        } else {
            throw new Passwd_Exception(_("Incorrect old password."));
        }    
    }

    /**
     * Format a password using the current encryption.
     *
     * @param string $plaintext  The plaintext password to encrypt.
     *
     * @return string  The crypted password.
     */
    function encryptPassword($plaintext)
    {
        return Horde_Auth::getCryptedPassword($plaintext,
                                        '',
                                        $this->_params['encryption'],
                                        $this->_params['show_encryption']);
    }

    /**
     * Change the user's password.
     *
     * @param string $username     The user for which to change the password.
     * @param string $oldpassword  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean  True or false based on success of the change.
     */
    function changePassword($username, $oldpassword, $new_password)
    {
        throw new Passwd_Exception(_("Backend not correctly implemented."));
    }

}
