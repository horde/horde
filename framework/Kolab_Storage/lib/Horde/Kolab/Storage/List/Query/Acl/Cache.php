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
extends Horde_Kolab_Storage_List_Query_Acl
implements Horde_Kolab_Storage_List_Manipulation_Listener,
Horde_Kolab_Storage_List_Synchronization_Listener
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
     * The underlying ACL query.
     *
     * @param Horde_Kolab_Storage_List_Query_Acl
     */
    private $_query;

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
     * @param Horde_Kolab_Storage_List_Query_Acl $acl The underlying acl query.
     * @param Horde_Kolab_Storage_List_Cache $cache The list cache.
     */
    public function __construct(Horde_Kolab_Storage_List_Query_Acl $query,
                                Horde_Kolab_Storage_List_Cache $cache)
    {
        $this->_query = $query;
        $this->_list_cache = $cache;
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
        if ($this->_list_cache->hasQuery(self::ALLRIGHTS)) {
            $this->_all_rights = $this->_list_cache->getQuery(self::ALLRIGHTS);
        } else {
            $this->_all_rights = array();
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
                 $this->_query->hasAclSupport()
             );
             $this->_list_cache->save();
        }
        return $this->_list_cache->hasSupport(self::CAPABILITY);
    }

    /**
     * Retrieve the access rights for a folder. This method will use two calls
     * to the backend. It will first get the individual user rights via
     * getMyRights and will subsequently fetch all ACL if the user has admin
     * rights on a folder. If you already know the user has admin rights on a
     * folder it makes more sense to call getAllAcl() directly.
     *
     * @param string $folder The folder to retrieve the ACL for.
     *
     * @return array An array of rights.
     */
    public function getAcl($folder)
    {
        if (!isset($this->_acl[$folder])) {
            $this->_acl[$folder] = $this->_query->getAcl($folder);
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
            $this->_my_rights[$folder] = $this->_query->getMyAcl($folder);
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
            $this->_all_rights[$folder] = $this->_query->getAllAcl($folder);
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
        $this->_query->setAcl($folder, $user, $acl);
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
        $this->_query->deleteAcl($folder, $user);
        $this->_purgeFolder($folder);
    }

    /**
     * Update the listener after creating a new folder.
     *
     * @param string $folder The path of the folder that has been created.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function updateAfterCreateFolder($folder, $type = null)
    {
    }

    /**
     * Update the listener after deleting folder.
     *
     * @param string $folder The path of the folder that has been deleted.
     *
     * @return NULL
     */
    public function updateAfterDeleteFolder($folder)
    {
        $this->_purgeFolder($folder);
    }

    /**
     * Update the listener after renaming a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function updateAfterRenameFolder($old, $new)
    {
        $this->_purgeFolder($old);
    }

    /**
     * Synchronize the ACL information with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
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