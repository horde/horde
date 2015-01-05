<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * The API for implementing password change systems for Passwd.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
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
     * @param $params  A hash containing connection parameters.
     */
    public function __construct(array $params = array())
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
            if (!$this->_params['show_encryption']) {
                $encrypted = $match[2];
            }
        } else {
            $encryption = $this->_params['encryption'];
        }

        $hashed = Horde_Auth::getCryptedPassword(
            $plaintext,
            $encrypted,
            $encryption,
            $this->_params['show_encryption']
        );

        if ($this->_params['show_encryption']) {
            /* Convert the hashing algorithm in both strings to uppercase. */
            $encrypted = preg_replace('/^({.*?})/e', "Horde_String::upper('\\1')", $encrypted);
            $hashed = preg_replace('/^({.*?})/e', "Horde_String::upper('\\1')", $hashed);
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
            $this->_params['show_encryption']
        );
    }

    /**
     * Changes the user's password.
     *
     * @param string $user     The user for which to change the password.
     * @param string $oldpass  The old (current) user password.
     * @param string $newpass  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($user, $oldpass, $newpass)
    {
        try {
            $user = Horde::callHook('username', array($user, $this), 'passwd');
        } catch (Horde_Exception_HookNotSet $e) {}

        $this->_changePassword($user, $oldpass, $newpass);
    }

    /**
     * Changes the user's password.
     *
     * @param string $user     The user for which to change the password
     *                         (converted to backend username).
     * @param string $oldpass  The old (current) user password.
     * @param string $newpass  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    abstract protected function _changePassword($user, $oldpass, $newpass);

}
