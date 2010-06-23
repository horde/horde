<?php
/**
 * This class represents a Kolab resource owner.
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
 * This class represents a Kolab resource owner.
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
class Horde_Kolab_FreeBusy_Owner_Kolab
{
    /**
     * The owner information.
     *
     * @var Horde_Kolab_FreeBusy_Params_Owner
     */
    private $_owner;

    /**
     * The user accessing the system.
     *
     * @var Horde_Kolab_FreeBusy_User
     */
    private $_user;

    /**
     * The connection to the user database.
     *
     * @var Horde_Kolab_FreeBusy_UserDb
     */
    private $_userdb;

    /**
     * The owner data retrieved from the user database.
     *
     * @var array
     */
    private $_owner_data;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Params_Owner $owner  The resource owner.
     * @param Horde_Kolab_FreeBusy_UserDb       $userdb The connection to the user database.
     * @param Horde_Kolab_FreeBusy_User         $user   The user accessing the system.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Params_Owner $owner,
        Horde_Kolab_FreeBusy_UserDb $userdb,
        Horde_Kolab_FreeBusy_User $user
    ) {
        $this->_owner  = $owner;
        $this->_userdb = $userdb;
        $this->_user   = $user;
    }

    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getPrimaryId()
    {
        if ($this->_user_data === null) {
            $this->_fetchOwnerData();
        }
        return $this->_user_data['mail'];
    }

    /**
     * Fetch the owner data from the user db.
     *
     * @return NULL
     */
    private function _fetchOwnerData()
    {
        try {
            $this->_owner_data = $this->_userdb->fetchUser($this->_owner->getId());
        } catch (Horde_Kolab_FreeBusy_Exception_UserNotFound $e) {
            $domain = $this->_user->getDomain();
            if (!empty($domain)) {
                $this->_fetchOwnerDataByAppendingUserDomain();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Fetch the owner data from the user db by appending the owner id with the
     * domain of the user currently accessing the system. This simply assumes
     * that they usually live in the same domain.
     *
     * @return NULL
     */
    private function _fetchOwnerDataByAppendingUserDomain()
    {
        $this->_owner_data = $this->_userdb->fetchUser(
            $this->_owner->getId() . '@' . $this->_user->getDomain()
        );
    }
}