<?php
/**
 * Horde_Block_Account_Base defines an API for getting/displaying account
 * information for a user for the accounts module.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde
 */
class Horde_Block_Account_Base
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Hash containing connection parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
    }

    /**
     * Returns the username.
     *
     * @return string  The lowercased username.
     *
     */
    public function getUsername()
    {
        return Horde_String::lower($this->_params['user']);
    }

    /**
     * Returns the user's quota if available.
     *
     * @return array  A quota array, elements are used bytes and limit bytes.
     */
    public function getQuota()
    {
        return array();
    }

    /**
     * Returns the user's full name.
     *
     * @return string  The user's full name.
     */
    public function getFullname()
    {
        return '';
    }

    /**
     * Returns the user's home (login) directory.
     *
     * @return string  The user's directory.
     */
    public function getHome()
    {
        return '';
    }

    /**
     * Returns the user's default shell.
     *
     * @return string  The user's shell.
     */
    public function getShell()
    {
        return '';
    }

    /**
     * Returns the date of the user's last password change.
     *
     * @return string  Date string.
     */
    public function getPasswordChange()
    {
        return '';
    }

    /**
     * Returns the status of the current password.
     *
     * @return string  A string with a warning message if the password is about
     *                 to expire.
     */
    public function checkPasswordStatus()
    {
        return '';
    }
}
