<?php
/**
 * Handles a list of folder acls.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Handles a list of folder acls.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_List_Query_Acl_Base
implements Horde_Kolab_Storage_List_Query_Acl
{
    /**
     * The queriable list.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List $list   The queriable list.
     * @param array                    $params Additional parameters.
     */
    public function __construct(Horde_Kolab_Storage_List $list,
                                $params)
    {
        $this->_list = $list;
        $this->_driver = $this->_list->getDriver();
    }

    /**
     * Does the backend support ACL?
     *
     * @return boolean True if the backend supports ACLs.
     */
    public function hasAclSupport()
    {
        return $this->_driver->hasAclSupport();
    }

    /**
     * Retrieve the access rights for a folder.
     *
     * @param string $folder The folder to retrieve the ACL for.
     *
     * @return array An array of rights.
     */
    public function getAcl($folder)
    {
        if (!$this->hasAclSupport()) {
            return array($this->_driver->getAuth() => 'lrid');
        }

        $acl = $this->getMyAcl($folder);
        if (strpos($acl, 'a') !== false) {
            try {
                return $this->_driver->getAcl($folder);
            } catch (Horde_Kolab_Storage_Exception $e) {
            }
        }

        return array($this->_driver->getAuth() => $acl);
    }

    /**
     * Retrieve the access rights the current user has on a folder.
     *
     * @param string $folder The folder to retrieve the user ACL for.
     *
     * @return string The user rights.
     */
    public function getMyAcl($folder)
    {
        if (!$this->hasAclSupport()) {
            return 'lrid';
        }
        return $this->_driver->getMyAcl($folder);
    }

    /**
     * Retrieve the all access rights on a folder.
     *
     * @param string $folder The folder to retrieve the ACL for.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return string The folder rights.
     */
    public function getAllAcl($folder)
    {
        if (!$this->hasAclSupport()) {
            return array($this->_driver->getAuth() => 'lrid');
        }
        return $this->_driver->getAcl($folder);
    }

    /**
     * Set the access rights for a folder.
     *
     * @param string $folder  The folder to act upon.
     * @param string $user    The user to set the ACL for.
     * @param string $acl     The ACL.
     *
     * @return NULL
     */
    public function setAcl($folder, $user, $acl)
    {
        $this->_failOnMissingAcl();
        return $this->_driver->setAcl($folder, $user, $acl);
    }

    /**
     * Delete the access rights for user on a folder.
     *
     * @param string $folder  The folder to act upon.
     * @param string $user    The user to delete the ACL for
     *
     * @return NULL
     */
    public function deleteAcl($folder, $user)
    {
        $this->_failOnMissingAcl();
        return $this->_driver->deleteAcl($folder, $user);
    }

    private function _failOnMissingAcl()
    {
        if (!$this->hasAclSupport()) {
            throw new Horde_Kolab_Storage_Exception('The backend does not support ACL.');
        }
    }

    /**
     * Create a new folder.
     *
     * @param string $folder The path of the folder to create.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function createFolder($folder, $type = null)
    {
    }

    /**
     * Delete a folder.
     *
     * @param string $folder The path of the folder to delete.
     *
     * @return NULL
     */
    public function deleteFolder($folder)
    {
    }

    /**
     * Rename a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function renameFolder($old, $new)
    {
    }

    /**
     * Return the last sync stamp.
     *
     * @return string The stamp.
     */
    public function getStamp()
    {
        return $this->_list->getStamp();
    }

    /**
     * Synchronize the ACL information with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
    }
}