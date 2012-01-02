<?php
/**
 * This class represents a Kolab user accessing the export system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class represents a Kolab accessing the export system.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_User_Kolab
extends Horde_Kolab_FreeBusy_UserDb_User_Kolab
implements Horde_Kolab_FreeBusy_User
{
    /**
     * The user name.
     *
     * @var string
     */
    private $_user;

    /**
     * Optional user password. 
     *
     * @var string
     */
    private $_pass;

    /**
     * Has the user authenticated successfully?
     *
     * @var boolean
     */
    private $_authenticated;

    /**
     * Constructor.
     *
     * @param string                       $user The user name.
     * @param Horde_Kolab_Server_Composite $db   The connection to the user database.
     * @param string                       $pass The user password.
     */
    public function __construct(
        $user, Horde_Kolab_Server_Composite $db, $pass = null
    ) {
        $this->_user = $user;
        $this->_pass = $pass;
        parent::__construct($db);
    }

    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getPrimaryId()
    {
        $id = parent::getPrimaryId();
        if (empty($id)) {
            return $this->_user;
        }
        return $id;
    }

    /**
     * Return the password of the user accessing the system.
     *
     * @return string The password.
     */
    public function getPassword()
    {
        return $this->_pass;
    }

    /**
     * Fetch the user data from the user db.
     *
     * @return NULL
     */
    protected function fetchUserDbUser()
    {
        return $this->fetchUser($this->_user);
    }

    /**
     * Finds out if a set of login credentials are valid.
     *
     * @return boolean Whether or not the password was correct.
     */
    public function isAuthenticated()
    {
        if ($this->_authenticated === null) {
            $this->_authenticated = parent::authenticate($this->_pass);
        }
        return $this->_authenticated;
    }
}