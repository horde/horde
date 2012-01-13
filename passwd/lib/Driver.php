<?php
/**
 * Passwd_Driver defines an API for implementing password change systems for
 * Passwd.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
 */
abstract class Passwd_Driver
{
    /**
     * Hash containing configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param $params   A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
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
        if (preg_match('/^{([^}]+)}(.*)/', $encrypted, $match)) {
            $encryption = Horde_String::lower($match[1]);
            $encrypted = $match[2];
        } else {
            $encryption = $this->_params['encryption'];
        }

        $hashed = Horde_Auth::getCryptedPassword(
            $plaintext,
            $encrypted,
            $encryption,
            $this->_params['show_encryption']);

        if ($this->_params['show_encryption']) {
            /* Convert the hashing algorithm in both strings to uppercase. */
            $encrypted = preg_replace(
                '/^({.*?})/e', "Horde_String::upper('\\1')", $encrypted);
            $hashed    = preg_replace(
                '/^({.*?})/e', "Horde_String::upper('\\1')", $hashed);
        }

        if ($encrypted != $hashed) {
            throw new Passwd_Exception(_("Incorrect old password."));
        }    
    }

    /**
     * Encrypts a password.
     *
     * @param string $plaintext  A plaintext password.
     *
     * @return string  The encrypted password.
     */
    protected function _encryptPassword($plaintext)
    {
        return Horde_Auth::getCryptedPassword(
            $plaintext,
            '',
            $this->_params['encryption'],
            $this->_params['show_encryption']);
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $oldpassword   The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    abstract public function changePassword($username, $oldpassword, $new_password);
}
