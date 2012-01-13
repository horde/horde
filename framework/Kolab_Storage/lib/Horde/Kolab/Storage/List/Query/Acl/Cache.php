<?php
/**
 * Handles a cached list of folder acls.
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
 * Handles a cached list of folder acls.
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
class Horde_Kolab_Storage_List_Query_Acl_Cache
extends Horde_Kolab_Storage_List_Query_Acl_Base
{
    /** The acl support */
    const CAPABILITY = 'ACL';

    /** The ACL query data */
    const ACL = 'ACL';

    /** The user specific rights */
    const MYRIGHTS = 'MYRIGHTS';

    /** All rights */
    const ALLRIGHTS = 'ALLRIGHTS';

    /**
     * The list cache.
     *
     * @var Horde_Kolab_Storage_Cache_List
     */
    private $_list_cache;

    /**
     * The cached ACL data.
     *
     * @var array
     */
    private $_acl;

    /**
     * The cached user rights.
     *
     * @var array
     */
    private $_my_rights;

    /**
     * The cached rights.
     *
     * @var array
     */
    private $_all_rights;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List $list   The queriable list.
     * @param array                    $params Additional parameters.
     */
    public function __construct(Horde_Kolab_Storage_List $list,
                                $params)
    {
        parent::__construct($list, $params);
        $this->_list_cache = $params['cache'];
        if ($this->_list_cache->hasQuery(self::ACL)) {
            $this->_acl = $this->_list_cache->getQuery(self::ACL);
        } else {
            $this->_acl = array();
        }
        if ($this->_list_cache->hasQuery(self::MYRIGHTS)) {
            $this->_my_rights = $this->_list_cache->getQuery(self::MYRIGHTS);
        } else {
            $this->_my_rights = array();
        }
    }

    /**
     * Does the backend support ACL?
     *
     * @return boolean True if the backend supports ACLs.
     */
    public function hasAclSupport()
    {
        if (!$this->_list_cache->issetSupport(self::CAPABILITY)) {
             $this->_list_cache->setSupport(
                 self::CAPABILITY,
                 parent::hasAclSupport()
             );
             $this->_list_cache->save();
        }
        return $this->_list_cache->hasSupport(self::CAPABILITY);
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
        if (!isset($this->_acl[$folder])) {
            $this->_acl[$folder] = parent::getAcl($folder);
            $this->_list_cache->setQuery(self::ACL, $this->_acl);
            $this->_list_cache->save();
        }
        return $this->_acl[$folder];
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
        if (!isset($this->_my_rights[$folder])) {
            $this->_my_rights[$folder] = parent::getMyAcl($folder);
            $this->_list_cache->setQuery(self::MYRIGHTS, $this->_my_rights);
            $this->_list_cache->save();
        }
        return $this->_my_rights[$folder];
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
        if (!isset($this->_all_rights[$folder])) {
            $this->_all_rights[$folder] = parent::getAllAcl($folder);
            $this->_list_cache->setQuery(self::ALLRIGHTS, $this->_all_rights);
            $this->_list_cache->save();
        }
        return $this->_all_rights[$folder];
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
        parent::setAcl($folder, $user, $acl);
        $this->_purgeFolder($folder);
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
        parent::deleteAcl($folder, $user);
        $this->_purgeFolder($folder);
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
        $this->_purgeFolder($folder);
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
        $this->_purgeFolder($old);
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
        $this->_acl = array();
        $this->_my_rights = array();
        $this->_all_rights = array();
    }

    /**
     * Remove outdated folder data from the cache.
     *
     * @param string $folder The folder name.
     *
     * @return NULL
     */
    private function _purgeFolder($folder)
    {
        unset($this->_acl[$folder]);
        unset($this->_my_rights[$folder]);
        unset($this->_all_rights[$folder]);
        $this->_list_cache->setQuery(self::ACL, $this->_acl);
        $this->_list_cache->setQuery(self::MYRIGHTS, $this->_my_rights);
        $this->_list_cache->setQuery(self::MYRIGHTS, $this->_all_rights);
        $this->_list_cache->save();
    }
}