<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

/**
 * An authentication backend for Sabre that wraps Horde's authentication.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Auth extends Sabre\DAV\Auth\Backend\AbstractBasic
{
    /**
     * Authentication object.
     *
     * @var Horde_Auth_Base
     */
    protected $_auth;

    /**
     * Constructor.
     *
     * @param Horde_Auth_Base $auth  An authentication object.
     */
    public function __construct(Horde_Auth_Base $auth)
    {
        $this->_auth = $auth;
    }

    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected function validateUserPass($username, $password)
    {
        return $this->_auth
            ->authenticate($username, array('password' => $password));
    }
}
