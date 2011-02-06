<?php
/**
 * Horde_Share_Kolab:: provides the Kolab backend for the horde share driver.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Share
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Share
 */

/**
 * Horde_Share_Kolab:: provides the Kolab backend for the horde share driver.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Share
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Share
 */
class Horde_Share_Kolab extends Horde_Share_Base
{
    const VERSION = 1;

    /**
     * The Kolab storage handler
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_storage;

    /**
     * The folder type in the storage backend.
     *
     * @var string
     */
    private $_type;

    /**
     * Constructor.
     *
     * @param string $app          The application that the shares belong to
     * @param string $user         The current user
     * @param Horde_Perms $perms   The permissions object
     * @param Horde_Group $groups  The Horde_Group object
     *
     */
    public function __construct($app, $user, Horde_Perms $perms, Horde_Group $groups)
    {
        switch ($app) {
        case 'mnemo':
            $this->_type = 'note';
            break;
        case 'kronolith':
            $this->_type = 'event';
            break;
        case 'turba':
            $this->_type = 'contact';
            break;
        case 'nag':
            $this->_type = 'task';
            break;
        default:
            throw new Horde_Share_Exception(sprintf(Horde_Share_Translation::t("The Horde/Kolab integration engine does not support \"%s\""), $app));
        }
        parent::__construct($app, $user, $perms, $groups);
    }

    /**
     * Set the Kolab storage backend.
     *
     * @param Horde_Kolab_Storage_List $driver The Kolab storage driver.
     *
     * @return NULL
     */
    public function setStorage(Horde_Kolab_Storage_List $driver)
    {
        $this->_storage = $driver;
    }

    /**
     * Return the Kolab storage backend associated with this driver.
     *
     * @return Horde_Kolab_Storage_List The Kolab storage driver.
     */
    public function getStorage()
    {
        if ($this->_storage === null) {
            throw new Horde_Share_Exception('The storage backend has not yet been set!');
        }
        return $this->_storage;
    }

    /**
     * Return the type of folder this share driver will access in the Kolab
     * storage backend (depends on the application calling the share driver).
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
    
    /**
     * Encode a share ID.
     *
     * @todo: In Horde3 share IDs were not properly escaped everywhere and it
     * made sense to escape them here just in case they are placed in a
     * URL. Needs checking, fixing and removal in Horde4.
     *
     * @param string $id The ID to be encoded.
     *
     * @return string The encoded ID.
     */
    private function _idEncode($id)
    {
        return rawurlencode($id);
    }

    /**
     * Decode a share ID.
     *
     * @param string $id The ID to be decoded.
     *
     * @return string The decoded ID.
     */
    private function _idDecode($id)
    {
        return rawurldecode($id);
    }

    /**
     * Returns a Horde_Share_Object_Kolab object corresponding to the given
     * share name, with the details retrieved appropriately.
     *
     * @param string $name  The name of the share to retrieve.
     * @param array  $data  The share data.
     *
     * @return Horde_Share_Object  The requested share.
     */
    private function _createObject($name, array $data = array())
    {
        $object = new Horde_Share_Object_Kolab($name, $this->_groups, $data);
        $this->initShareObject($object);
        return $object;
    }

    /**
     * Returns a Horde_Share_Object_Kolab object corresponding to the given
     * share name, with the details retrieved appropriately.
     *
     * @param string $name  The name of the share to retrieve.
     *
     * @return Horde_Share_Object  The requested share.
     * @throws Horde_Exception_NotFound
     * @throws Horde_Share_Exception
     */
    protected function _getShare($name)
    {
        $list = $this->getStorage()
            ->getQuery()
            ->dataByType($this->_type);

        $query = $this->getStorage()
            ->getQuery(Horde_Kolab_Storage_List::QUERY_SHARE);

        foreach (array_keys($list) as $folder) {
            $data = $query->getParameters($folder);
            if (isset($data['share_name']) && $data['share_name'] == $name) {
                return $this->getShareById($this->_idEncode($folder));
            }
        }
        return $this->getShareById($name);
    }

    /**
     * Returns a Horde_Share_Object_sql object corresponding to the given
     * unique ID, with the details retrieved appropriately.
     *
     * @param integer $id  The id of the share to retrieve.
     *
     * @return Horde_Share_Object_sql  The requested share.
     * @throws Horde_Share_Exception, Horde_Exception_NotFound
     */
    protected function _getShareById($id)
    {
        $list = $this->getStorage()
            ->getQuery()
            ->dataByType($this->_type);

        if (!isset($list[$this->_idDecode($id)])) {
            $this->_logger->err(sprintf('Share id %s not found', $id));
            throw new Horde_Exception_NotFound();
        }

        $query = $this->getStorage()
            ->getQuery(Horde_Kolab_Storage_List::QUERY_SHARE);

        $data = array_merge(
            $query->getParameters($this->_idDecode($id)),
            $list[$this->_idDecode($id)]
        );
        $data['desc'] = $query->getDescription($this->_idDecode($id));
        return $this->_createObject($id, $data);
    }

    /**
     * Returns an array of Horde_Share_Object_kolab objects corresponding to
     * the requested folders.
     *
     * @param string $ids  The ids of the shares to fetch.
     *
     * @return array  An array of Horde_Share_Object_kolab objects.
     */
    protected function _getShares(array $ids)
    {
        $objects = array();
        foreach ($ids as $id) {
            $objects[$id] = $this->_getShareById($id);
        }
        return $objects;
    }

    /**
     * Checks if a share exists in the system.
     *
     * @param string $share  The share to check.
     *
     * @return boolean  True if the share exists.
     * @throws Horde_Share_Exception
     */
    protected function _exists($share)
    {
        try {
            $this->getShare($share);
            return true;
        } catch (Horde_Exception_NotFound $e) {
            return false;
        }
    }

    /**
     * Check that a share id exists in the system.
     *
     * @param integer $id  The share id
     *
     * @return boolean True if the share exists.
     */
    protected function _idExists($id)
    {
        return in_array(
            $this->_idDecode($id),
            $this->getStorage()
            ->getQuery()
            ->listByType($this->_type)
        );
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param array  $params     See listShares().
     *
     * @return array  The shares the user has access to.
     */
    protected function _listShares($userid, array $params = array())
    {
        return array_map(
            array($this, '_idEncode'),
            $this->getStorage()
            ->getQuery()
            ->listByType($this->_type)
        );

        $key = serialize(array($userid, $params['perm'], $params['attributes']));
        if ($this->_storage === false) {
            $this->_listcache[$key] = array();
        } else if (empty($this->_listcache[$key])
            || $this->_list->validity != $this->_listCacheValidity) {
            $sharelist = $this->_storage->getByType($this->_type);
            if ($sharelist instanceof PEAR_Error) {
                throw new Horde_Share_Exception($sharelist->getMessage());
            }

            $shares = array();
            foreach ($sharelist as $folder) {
                $id = $folder->getShareId();
                $share = $this->getShare($id);
                $keep = true;
                if (!$share->hasPermission($userid, $params['perm'])) {
                    $keep = false;
                }
                if (isset($params['attributes']) && $keep) {
                    if (is_array($params['attributes'])) {
                        foreach ($params['attributes'] as $key => $value) {
                            if (!$share->get($key) == $value) {
                                $keep = false;
                                break;
                            }
                        }
                    } elseif (!$share->get('owner') == $params['attributes']) {
                        $keep = false;
                    }
                }
                if ($keep) {
                    $shares[] = $id;
                }
            }
            $this->_listcache[$key] = $shares;
            $this->_listCacheValidity = $this->_storage->validity;
        }

        return $this->_listcache[$key];
    }


    /**
     * Lists *all* shares for the current app/share, regardless of
     * permissions.
     *
     * For the Kolab backend this cannot work in the same way as for the SQL
     * based backend. Permissions are always handled by the backend automatically (IMAP ACLs) and cannot be disabled.
     *
     * listAllShares() is apparently used during command line scipts where it
     * represents administrator access. This is possible on Kolab by using the
     * "manager" user. In that case a standard listShares() authenticated as
     * "manager" should be sufficient.
     *
     * @return array  All shares for the current app/share.
     */
    protected function _listAllShares()
    {
        return array();
    }

    /**
     * Returns a new share object.
     *
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object_kolab  A new share object.
     */
    protected function _newShare($name)
    {
        return $this->_createObject(
            null,
            array(
                'type' => $this->_type,
                'share_name' => $name
            )
        );
    }

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with Horde_Share::newShare(),
     * and have any initial details added to it, before this function is
     * called.
     *
     * @param Horde_Share_Object $share  The new share object.
     */
    protected function _addShare(Horde_Share_Object $share)
    {
        $share->save();
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object $share  The share to remove.
     */
    protected function _removeShare(Horde_Share_Object $share)
    {
        $this->getStorage()->deleteFolder($this->_idDecode($share->getId()));
    }

    /**
     * Retrieve the Kolab specific access rights for a share.
     *
     * @param string $id The share ID.
     *
     * @return An array of rights.
     */
    public function getAcl($id)
    {
        return $this->getStorage()
            ->getQuery(Horde_Kolab_Storage_List::QUERY_ACL)
            ->getAcl(
                $this->_idDecode($id)
            );
    }

    /**
     * Set the Kolab specific access rights for a share.
     *
     * @param string $id   The share ID.
     * @param string $user The user to set the ACL for.
     * @param string $acl  The ACL.
     *
     * @return NULL
     */
    public function setAcl($id, $user, $acl)
    {
        $this->getStorage()
            ->getQuery(Horde_Kolab_Storage_List::QUERY_ACL)
            ->setAcl(
                $this->_idDecode($id), $user, $acl
            );
    }

    /**
     * Delete Kolab specific access rights for a share.
     *
     * @param string $id   The share ID.
     * @param string $user The user to delete the ACL for
     *
     * @return NULL
     */
    public function deleteAcl($id, $user)
    {
        $this->getStorage()
            ->getQuery(Horde_Kolab_Storage_List::QUERY_ACL)
            ->deleteAcl(
                $this->_idDecode($id), $user
            );
    }

    /**
     * Generate the Kolab share ID based on the share name attribute.
     *
     * @param string $name  The share name.
     * @param string $owner The owner of the share.
     *
     * @return string The (new) share id.
     */
    public function generateId($name, $owner)
    {
        if ($owner == $this->_user) {
            return $this->_idEncode(
                $this->getStorage()->getNamespace()->setTitle($name)
            );
        } else {
            return $this->_idEncode(
                $this->getStorage()->getNamespace()->setTitleInOther($name, $owner)
            );
        }
    }

    /**
     * Save share data to the storage backend.
     *
     * @param string $id          The share id.
     * @param string $old_id      The old share id.
     * @param array  $data        The share data.
     *
     * @return NULL
     */
    public function save($id, $old_id, $data)
    {
        if ($old_id === null) {
            $this->getStorage()->createFolder(
                $this->_idDecode($id), $this->_type
            );
        } else if ($id != $old_id) {
            $this->getStorage()->renameFolder(
                $this->_idDecode($old_id), $this->_idDecode($id), $this->_type
            );
        }

        $query = $this->getStorage()
            ->getQuery(Horde_Kolab_Storage_List::QUERY_SHARE);
        if (isset($data['desc'])) {
            $query->setDescription($this->_idDecode($id), $data['desc']);
        }
        unset($data['desc']);
        unset($data['owner']);
        unset($data['name']);
        $query->setParameters($this->_idDecode($id), $data);
    }
}
