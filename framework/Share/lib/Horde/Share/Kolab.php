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
     * A map of IDs to folder names.
     *
     * @var array
     */
    private $_id_map = array();

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
     * @param Horde_Kolab_Storage $storage The Kolab storage handler.
     *
     * @return NULL
     */
    public function setStorage(Horde_Kolab_Storage $storage)
    {
        $this->_storage = $storage->getList();
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
     * URL. Needs checking in Horde4.
     *
     * @param string $id The ID to be encoded.
     *
     * @return string The encoded ID.
     */
    private function _idEncode($id)
    {
        $folder = $this->_storage->getFolder($id);
        return $this->constructId($folder->getOwner(), $folder->getSubpath());
    }

    /**
     * Construct the ID from the owner name and the folder subpath.
     *
     * @param string $owner The share owner.
     * @param string $name  The name of the folder without the namespace part.
     *
     * @return string The encoded ID.
     */
    public function constructId($owner, $name)
    {
        return base64_encode(serialize(array($owner, $name)));
    }

    /**
     * Construct the Kolab storage folder name based on the share name and owner
     * attributes.
     *
     * @param string $name  The share name.
     * @param string $owner The owner of the share.
     *
     * @return string The folder name for the Kolab backend.
     */
    public function constructFolderName($owner, $name)
    {
        if ($owner == $this->_user) {
            return $this->getStorage()->getNamespace()->setTitle($name);
        } else {
            return $this->getStorage()->getNamespace()->setTitleInOther($name, $owner);
        }
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
        if (!isset($this->_id_map[$id])) {
            list($owner, $name) = $this->_idDeconstruct($id);
            $this->_id_map[$id] = $this->constructFolderName($owner, $name);
        }
        return $this->_id_map[$id];
    }

    /**
     * Deconstruct the ID elements from the ID string
     *
     * @param string $id The ID to be deconstructed.
     *
     * @return array A tuple of (owner, folder subpath).
     */
    private function _idDeconstruct($id)
    {
        if (!$id = base64_decode($id)) {
            $msg = sprintf('Share id %s is invalid.', $id);
            $this->_logger->err($msg);
            throw new Horde_Exception_NotFound($msg);
        }
        if (!$id = @unserialize($id)) {
            $msg = sprintf('Share id %s is invalid.', $id);
            $this->_logger->err($msg);
            throw new Horde_Exception_NotFound($msg);
        }
        return $id;
    }


    /**
     * Returns a Horde_Share_Object_Kolab object corresponding to the given
     * share name, with the details retrieved appropriately.
     *
     * @param string $id    The id of the share to retrieve.
     * @param array  $data  The share data.
     *
     * @return Horde_Share_Object  The requested share.
     */
    private function _createObject($id, array $data = array())
    {
        $object = new Horde_Share_Object_Kolab($id, $this->_groups, $data);
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

        foreach ($list as $folder => $folder_data) {
            $data = $query->getParameters($folder);
            if (isset($data['share_name']) && $data['share_name'] == $name) {
                return $this->getShareById(
                    $this->constructId(
                        $folder_data['owner'], $folder_data['name']
                    )
                );
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
            $msg = sprintf('Share id %s not found', $id);
            $this->_logger->err($msg);
            throw new Horde_Exception_NotFound($msg);
        }

        $query = $this->getStorage()
            ->getQuery(Horde_Kolab_Storage_List::QUERY_SHARE);

        $data = array_merge(
            $query->getParameters($this->_idDecode($id)),
            $list[$this->_idDecode($id)]
        );
        $data['desc'] = $query->getDescription($this->_idDecode($id));
        if (isset($data['parent'])) {
            $data['parent'] = $this->_idEncode($data['parent']);
        }
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
            $share = $this->_getShareById($id);
            $objects[$share->getName()] = $share;
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
        $key = md5(serialize(array($userid, $params)));

        if (!isset($this->_listcache[$key])) {
            $shares = array_map(
                array($this, '_idEncode'),
                $this->getStorage()
                ->getQuery()
                ->listByType($this->_type)
            );
            $remove = array();
            if ($params['perm'] != Horde_Perms::SHOW || empty($userid)) {
                foreach ($shares as $share) {
                    $object = $this->getShareById($share);
                    if (!$object->hasPermission($userid, $params['perm'], $object->get('owner'))) {
                        $remove[] = $share;
                    }
                }
            }
            if (isset($params['parent'])) {
                foreach ($shares as $share) {
                    $object = $this->getShareById($share);
                    if ($params['parent'] instanceOf Horde_Share_Object) {
                        $parent = $params['parent'];
                    } else {
                        $parent = $this->getShare($params['parent']);
                    }
                    if (!$object->getParent() ||  $object->getParent()->getId() != $parent->getId()) {
                        $remove[] = $share;
                    }
                }
            }
            if (isset($params['all_levels']) && empty($params['all_levels'])) {
                foreach ($shares as $share) {
                    $object = $this->getShareById($share);
                    $parent = $object->get('parent');
                    if (!empty($parent) && in_array($parent, $shares)) {
                        $remove[] = $share;
                    }
                }
            }
            if (isset($params['attributes'])) {
                if (!is_array($params['attributes'])) {
                    $attributes = array('owner' => $params['attributes']);
                } else {
                    $attributes = $params['attributes'];
                }
                foreach ($shares as $share) {
                    $object = $this->getShareById($share);
                    foreach ($attributes as $key => $value) {
                        if ($object->get($key) != $value) {
                            $remove[] = $share;
                        }
                    }
                }
            }
            if (!empty($remove)) {
                $shares = array_diff($shares, $remove);
            }
            if (isset($params['sort_by'])) {
                if ($params['sort_by'] == 'id') {
                    sort($shares);
                } else {
                    $sorted = array();
                    foreach ($shares as $share) {
                        $object = $this->getShareById($share);
                        $key = $object->get($params['sort_by']);
                        $sorted[$key] = $object->getId();
                    }
                    ksort($sorted);
                    $shares = array_values($sorted);
                }
            }
            if (!empty($params['direction'])) {
                $shares = array_reverse($shares);
            }
            if (isset($params['from']) && !empty($params['from'])) {
                $shares = array_slice($shares, $params['from']);
            }
            if (isset($params['count']) && !empty($params['count'])) {
                $shares = array_slice($shares, 0, $params['count']);
            }
            $this->_listcache[$key] = $shares;
        }
        return $this->_listcache[$key];
    }

    /**
     * Returns the count of all shares that $userid has access to.
     *
     * @param string  $userid      The userid of the user to check access for.
     * @param integer $perm        The level of permissions required.
     * @param mixed   $attributes  Restrict the shares counted to those
     *                             matching $attributes. An array of
     *                             attribute/values pairs or a share owner
     *                             username.
     * @param mixed  $parent      The share to start searching from
     *                            (Horde_Share_Object, share_id, or null)
     * @param boolean $allLevels  Return all levels, or just the direct
     *                            children of $parent?
     *
     * @return integer  Number of shares the user has access to.
     * @throws Horde_Share_Exception
     */
    public function countShares($userid, $perm = Horde_Perms::SHOW,
        $attributes = null, $parent = null, $allLevels = true)
    {
        return count(
            $this->listShares(
                $userid,
                array(
                    'perm' => $perm,
                    'attributes' => $attributes,
                    'parent' => $parent,
                    'all_levels' => $allLevels
                )
            )
        );
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
        unset(
            $data['desc'],
            $data['owner'],
            $data['name'],
            $data['default'],
            $data['parent'],
            $data['folder']
        );
        $query->setParameters($this->_idDecode($id), $data);
    }
}
