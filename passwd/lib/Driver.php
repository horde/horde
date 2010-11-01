<?php
/**
 * Passwd_Driver:: defines an API for implementing password change systems for
 * Passwd.
 *
 * $Horde: passwd/lib/Driver.php,v 1.44.2.10 2009/01/06 15:25:15 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
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
    var $_params = array();

    /**
     * Error string that will be returned to the user if an error occurs.
     *
     * @var string
     */
    var $_errorstr;

    /**
     * Constructs a new expect Passwd_Driver object.
     *
     * @param $params   A hash containing connection parameters.
     */
    function Passwd_Driver($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Compare a plaintext password with an encrypted password.
     *
     * @return mixed  True if they match, PEAR_Error if they differe.
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

        $hashed = Auth::getCryptedPassword($plaintext,
                                           $encrypted,
                                           $encryption,
                                           false);

        if ($this->_params['show_encryption']) {
            /* Convert the hashing algorithm in both strings to uppercase. */
            $encrypted = preg_replace('/^({.*?})/e', "String::upper('\\1')", $encrypted);
            $hashed = preg_replace('/^({.*?})/e', "String::upper('\\1')", $hashed);
        }

        return ($encrypted == $hashed) ? true : PEAR::raiseError(_("Incorrect old password."));
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
        return Auth::getCryptedPassword($plaintext,
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
        return PEAR::raiseError(_("Backend not correctly implemented."));
    }

    /**
     * Attempts to return a concrete Passwd_Driver instance based on
     * $driver.
     *
     * @param string $driver  The type of concrete passwd_Driver subclass
     *                        to return. The is based on the passwd
     *                        driver ($driver). The code is dynamically
     *                        included.
     *
     * @param array  $params  (optional) A hash containing any additional
     *                        configuration or connection parameters a
     *                        subclass might need.
     *
     * @return mixed  The newly created concrete Passwd_Driver
     *                instance, or false on an error.
     * @throws Passwd_Exception
     */
    function factory($driver, $params = array())
    {
        $driver = basename($driver);
        require_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Passwd_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Passwd_Exception(sprintf(_("No such backend \"%s\" found."), $driver));
    }

}
