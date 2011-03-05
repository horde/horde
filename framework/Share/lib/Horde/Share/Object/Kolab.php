<?php
/**
 * Extension of the Horde_Share_Object class for handling Kolab share
 * information.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Share
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Share
 */

/**
 * Extension of the Horde_Share_Object class for handling Kolab share
 * information.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Share
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Share
 */
class Horde_Share_Object_Kolab
extends Horde_Share_Object
implements Serializable, Horde_Perms_Permission_Kolab_Storage
{
    /**
     * Serializable version.
     */
    const VERSION = 2;

    /**
     * The old share id.
     *
     * @var string
     */
    private $_old_id;

    /**
     * The share id.
     *
     * @var string
     */
    private $_id;

    /**
     * The Horde_Group driver
     *
     * @var Horde_Group_Base
     */
    private $_groups;

    /**
     * The share attributes.
     *
     * @var array
     */
    protected $_data;

    /**
     * The permission cache holds any permission changes that will be executed
     * on saving.
     *
     * @var Horde_Perm_Permission_Kolab
     */
    private $_permission;

    /**
     * Constructor.
     *
     * @param string $id                The share id.
     * @param Horde_Group_Base $groups  The Horde_Group driver
     * @param array $data               The share data.
     */
    public function __construct($id, Horde_Group_Base $groups,
                                array $data = array())
    {
        $this->_old_id = $id;
        $this->_id     = $id;
        $this->_groups = $groups;
        $this->_data   = $data;
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return serialize(array(
            self::VERSION,
            $this->_id,
            $this->_data,
            $this->_shareCallback
        ));
    }

    /**
     * Unserialize object.
     *
     * @param <type> $data
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_id = $data[1];
        $this->_data = $data[2];
        if (empty($data[3])) {
            throw new Exception('Missing callback for unserializing Horde_Share_Object');
        }
        $this->_shareCallback = $data[3];
        $this->setShareOb(call_user_func($this->_shareCallback));
    }

    /**
     * Returns the ID of this share.
     *
     * @return string  The share's ID.
     */
    public function getId()
    {
        if ($this->_id === null) {
            throw new Horde_Share_Exception(
                'A new Kolab share requires a set("name", ...) and set("owner", ...) call before the ID is available.'
            );
        }
        return $this->_id;
    }

    /**
     * Returns the permission ID of this share.
     *
     * @return string  The share's permission ID.
     */
    public function getPermissionId()
    {
        if ($this->_id === null) {
            /**
             * We only get here if permissions are being set before the name has
been set. For the Kolab permission instance it should not matter if the name is
a random string.
            */
            return md5(pack('I', mt_rand()));
        }
        return $this->_id;
    }

    /**
     * Returns the name of this share.
     *
     * @return string  The share's name.
     */
    public function getName()
    {
        if (isset($this->_data['share_name'])) {
            return $this->_data['share_name'];
        } else {
            return $this->getId();
        }
    }

    /**
     * Returns the owner of this share.
     *
     * @return string  The share's owner.
     */
    public function getOwner()
    {
        return $this->get('owner');
    }

    /**
     * Returns an attribute value from this object.
     *
     * @param string $attribute  The attribute to return.
     *
     * @return mixed The value for $attribute.
     */
    public function get($attribute)
    {
        if (isset($this->_data[$attribute])) {
            return $this->_data[$attribute];
        }
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     *
     * @return NULL
     */
    public function set($attribute, $value)
    {
        if ($attribute == 'owner') {
            $this->_id = $this->getShareOb()->constructId(
                $value, $this->get('name')
            );
            $this->_data['folder'] = $this->getShareOb()->constructFolderName(
                $value, $this->get('name')
            );
        }
        if ($attribute == 'name') {
            $this->_id = $this->getShareOb()->constructId(
                $this->get('owner'), $value
            );
            $this->_data['folder'] = $this->getShareOb()->constructFolderName(
                $this->get('owner'), $value
            );
        }
        $this->_data[$attribute] = $value;
    }

    /**
     * Return a count of the number of children this share has
     *
     * @param string $user        The user to use for checking perms
     * @param integer $perm       A Horde_Perms::* constant
     * @param boolean $allLevels  Count grandchildren or just children
     *
     * @return integer  The number of child shares
     */
    public function countChildren($user, $perm = Horde_Perms::SHOW, $allLevels = true)
    {
        return $this->getShareOb()->countShares($user, $perm, null, $this, $allLevels);
    }

    /**
     * Get all children of this share.
     *
     * @param string $user        The user to use for checking perms
     * @param integer $perm       Horde_Perms::* constant. If NULL will return
     *                            all shares regardless of permissions.
     * @param boolean $allLevels  Return all levels.
     *
     * @return array  An array of Horde_Share_Object objects
     */
    public function getChildren($user, $perm = Horde_Perms::SHOW, $allLevels = true)
    {
        return $this->getShareOb()->listShares(
            $user, array('perm' => $perm,
                         'direction' => 1,
                         'parent' => $this,
                         'all_levels' => $allLevels));
    }

    /**
     * Returns a child's direct parent.
     *
     * @return Horde_Share_Object|NULL The direct parent Horde_Share_Object or
     *                                 NULL in case the share has no parent.
     */
    public function getParent()
    {
        $parent_id = $this->get('parent');
        if (!empty($parent_id)) {
            try {
                return $this->getShareOb()->getShareById($parent_id);
            } catch (Horde_Exception_NotFound $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get all of this share's parents.
     *
     * @return array()  An array of Horde_Share_Objects
     */
    public function getParents()
    {
        $parents = array();
        $share = $this->getParent();
        while ($share instanceof Horde_Share_Object) {
            $parents[] = $share;
            $share = $share->getParent();
        }

        return array_reverse($parents);
    }

    /**
     * Set the parent object for this share.
     *
     * @param mixed $parent A Horde_Share object or share id for the parent.
     *
     * @return NULL
     */
    public function setParent($parent)
    {
        if (!$parent instanceof Horde_Share_Object) {
            $parent = $this->getShareOb()->getShareById($parent);
        }
        $this->set('name', $parent->get('name') . ':' . $this->get('name'));
    }

    /**
     * Saves the current attribute values.
     */
    protected function _save()
    {
        if (!isset($this->_data['share_name'])) {
            $this->_data['share_name'] = $this->getName();
        }
        $this->getShareOb()->save($this->getId(), $this->_old_id, $this->_data);
        $this->_old_id = $this->_id;
        $this->getPermission()->save();
    }

    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the shared object.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    public function hasPermission($userid, $permission, $creator = null)
    {
        return $this->getShareOb()->getPermsObject()->hasPermission($this->getPermission(), $userid, $permission, $creator);
    }

    /**
     * Returns the permissions from this storage object.
     *
     * @return Horde_Perms_Permission_Kolab The permissions on the share.
     */
    public function getPermission()
    {
        if ($this->_permission === null) {
            $this->_permission = new Horde_Perms_Permission_Kolab($this, $this->_groups);
        }
        return $this->_permission;
    }

    /**
     * Sets the permissions on the share.
     *
     * @param Horde_Perms_Permission_Kolab $perms  Permission object to folder on the
     *                                             object.
     * @param boolean                      $update Save the updated information?
     *
     * @return NULL
     */
    public function setPermission($perms, $update = true)
    {
        if (!$perms instanceOf Horde_Perms_Permission_Kolab) {
            $this->getPermission()->setData($perms->getData());
        } else {
            $this->_permission = $perms;
        }
        if ($update) {
            $this->save();
        }
    }

    /**
     * Retrieve the Kolab specific access rights for this share.
     *
     * @return An array of rights.
     */
    public function getAcl()
    {
        if ($this->_old_id === null) {
            // The share has not been created yet.
            return array(
                $this->get('owner') => Horde_Perms_Permission_Kolab::ALL
            );
        }
        return $this->getShareOb()->getAcl($this->_id);
    }

    /**
     * Set the Kolab specific access rights for this share.
     *
     * @param string $user The user to set the ACL for.
     * @param string $acl  The ACL.
     *
     * @return NULL
     */
    public function setAcl($user, $acl)
    {
        return $this->getShareOb()->setAcl($this->_id, $user, $acl);
    }

    /**
     * Delete Kolab specific access rights for this share.
     *
     * @param string $user The user to delete the ACL for.
     *
     * @return NULL
     */
    public function deleteAcl($user)
    {
        return $this->getShareOb()->deleteAcl($this->_id, $user);
    }
}
