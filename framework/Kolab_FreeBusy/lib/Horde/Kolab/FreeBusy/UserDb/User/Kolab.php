<?php
/**
 * This interface represents a user from the user database.
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
 * This interface represents a user from the user database.
 *
 * Copyright 2010 Kolab Systems AG
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
abstract class Horde_Kolab_FreeBusy_UserDb_User_Kolab
{
    /**
     * The connection to the database.
     *
     * @var Horde_Kolab_Server_Composite
     */
    private $_db;

    /**
     * The user representation.
     *
     * @var ???
     */
    private $_user;

    /**
     * The user ID in the db.
     *
     * @var string
     */
    private $_guid;

    /**
     * The representation of the server configuration.
     *
     * @var ???
     */
    private $_server;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Composite $db The connection to the server.
     */
    public function __construct(Horde_Kolab_Server_Composite $db)
    {
        $this->_db = $db;
    }

    protected function getServer()
    {
        if ($this->_server === null) {
            $this->_server = $this->_validate(
                $this->_db->fetch(
                    sprintf('k=kolab,%s', $this->_db->getBaseUid()),
                    KOLAB_OBJECT_SERVER
                )
            );
        }
        return $this->_server;
    }

    protected function getUserDbUser()
    {
        if ($this->_user === null) {
            $this->_user = $this->fetchUserDbUser();
        }
        return $this->_user;
    }

    abstract protected function fetchUserDbUser();

    protected function fetchUser($user)
    {
        try {
            $this->_guid = $this->_db->search->searchGuidForUidOrMail($user);
            return $this->_db->objects->fetch(
                $this->_guid,
                'Horde_Kolab_Server_Object_Kolab_User'
            );
        } catch (Horde_Kolab_Server_Exception $e) {
            throw new Horde_Kolab_FreeBusy_Exception($e);
        }
    }

    protected function fetchOwner($owner)
    {
        $this->_guid = $this->_validate(
            $this->_db->uidForMailOrIdOrAlias($owner)
        );
        if ($this->_guid === false) {
            throw new Horde_Kolab_FreeBusy_Exception(sprintf('Unknown owner "%s"!', $owner));
        }
        return $this->_validate(
            $this->_db->fetch($this->_guid, KOLAB_OBJECT_USER)
        );
    }

    /**
     * Finds out if the provided password is valid for this user.
     *
     * @param string $pass The password.
     *
     * @return boolean Whether or not the password was correct.
     */
    protected function authenticate($pass)
    {
        try {
            $this->_db->server->connectGuid($this->_guid, $pass);
        } catch (Horde_Kolab_Server_Exception_Bindfailed $e) {
            return false;
        }
        return true;
    }

    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getGuid()
    {
        $this->getUserDbUser();
        return $this->_guid;
    }

    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getPrimaryId()
    {
        return $this->getMail();
    }

    /**
     * Return the mail address of the resource owner.
     *
     * @return string The mail address.
     */
    public function getMail()
    {
        return $this->_validate($this->getUserDbUser()->getSingle('mail'));
    }

    /**
     * Return the primary domain of the user accessing the system.
     *
     * @return string The primary domain.
     */
    public function getDomain()
    {
        $mail = $this->getMail();
        $idx = strpos($mail, '@');
        if ($idx !== false) {
            return substr($mail, $idx + 1);
        } else {
            return '';
        }
    }

    /**
     * Return the name of the resource owner.
     *
     * @return string The name of the owner.
     */
    public function getName()
    {
        return $this->_validate($this->getUserDbUser()->get(KOLAB_ATTR_CN));
    }

    /**
     * Indicates the correct free/busy server for the resource owner.
     *
     * @return string The server name.
     */
    public function getFreeBusyServer()
    {
        return $this->_validate($this->getUserDbUser()->getServer('freebusy'));
    }

    /**
     * Return how many days into the past the free/busy data should be
     * calculated for this owner.
     *
     * @return int The number of days.
     */
    public function getFreeBusyPast()
    {
        return $this->_validate($this->getServer()->get(KOLAB_ATTR_FBPAST));
    }

    /**
     * Return how many days into the future the free/busy data should be
     * calculated for this owner.
     *
     * @return int The number of days.
     */
    public function getFreeBusyFuture()
    {
        return $this->_validate($this->getUserDbUser()->get(KOLAB_ATTR_FBFUTURE));
    }

    /**
     * Return the groups this user is member of.
     *
     * @return array The groups for this user.
     */
    public function getGroups()
    {
        return $this->_validate($this->getUserDbUser()->getGroupAddresses());
    }

    protected function _validate($result)
    {
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Kolab_FreeBusy_Exception($result->getMessage(), $result->getCode());
        }
        return $result;
    }
}
