<?php
/**
 * This class represents a Kolab user accessing the export system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class represents a Kolab accessing the export system.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_User_Kolab
{
    /**
     * The user details.
     *
     * @var Horde_Kolab_FreeBusy_Params_User
     */
    private $_user;

    /**
     * The connection to the user database.
     *
     * @var Horde_Kolab_FreeBusy_UserDB
     */
    private $_userdb;

    /**
     * The user data retrieved from the user database.
     *
     * @var array
     */
    private $_user_data;

    /**
     * Has the user authenticated successfully?
     *
     * @var boolean
     */
    private $_authenticated;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Params_User $user   The user parameters.
     * @param Horde_Kolab_FreeBusy_UserDb      $userdb The connection to the user database.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Params_User $user,
        Horde_Kolab_FreeBusy_UserDb $userdb
    ) {
        $this->_user   = $user;
        $this->_userdb = $userdb;
    }

    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getPrimaryId()
    {
        if ($this->_user_data === null) {
            $this->_fetchUserData();
        }
        return $this->_user_data['mail'];
    }

    /**
     * Return the primary domain of the user accessing the system.
     *
     * @return string The primary domain.
     */
    public function getDomain()
    {
        if ($this->_user_data === null) {
            $this->_fetchUserData();
        }
        return $this->_user_data['domain'];
    }

    /**
     * Fetch the user data from the user db.
     *
     * @return NULL
     */
    private function _fetchUserData()
    {
        $this->_user_data = $this->_userdb->fetchUser($this->_user->getId());
        $idx = strpos($this->_user->getPrimaryId(), '@');
        if ($idx !== false) {
            $this->_user_data['domain'] = substr(
                $this->_user->getPrimaryId(), $idx + 1
            );
        } else {
            $this->_user_data['domain'] = '';
        }
    }

    /**
     * Finds out if a set of login credentials are valid.
     *
     * @return boolean Whether or not the password was correct.
     */
    public function isAuthenticated()
    {
        if ($this->_authenticated === null) {
            list($user, $pass) = $this->_user->getCredentials();
            try {
                $this->_userdb->connect($user, $pass);
                $this->_authenticated = true;
            } catch (Horde_Kolab_Server_Exception_Bindfailed $e) {
                $this->_authenticated = false;
            }
        }
        return $this->_authenticated;
    }
}