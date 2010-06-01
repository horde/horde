<?php
/**
 * Extension of the DataTreeObject class for storing Group information
 * in the Categories driver. If you want to store specialized Group
 * information, you should extend this class instead of extending
 * DataTreeObject directly.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_DataTreeObject extends DataTreeObject
{
    /**
     * The Group object which this group is associated with - needed
     * for updating data in the backend to make changes stick, etc.
     *
     * @var Horde_Group
     */
    protected $_groupOb;

    /**
     * This variable caches the users added or removed from the group
     * for History logging of user-groups relationship.
     *
     * @var array
     */
    protected $_auditLog = array();

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_class_vars(__CLASS__)), array('_datatree', '_groupOb'));
    }

    /**
     * Associates a group object with this group.
     *
     * @param Horde_Group $groupOb  The group object.
     */
    public function setGroupOb($groupOb)
    {
        $this->_groupOb = $groupOb;
    }

    /**
     * Fetch the ID of this group
     *
     * @return string The group's ID
     */
    public function getId()
    {
        return $this->_groupOb->getGroupId($this);
    }

    /**
     * Save any changes to this object to the backend permanently.
     */
    public function save()
    {
        return $this->_groupOb->updateGroup($this);

    }

    /**
     * Adds a user to this group, and makes sure that the backend is
     * updated as well.
     *
     * @param string $username The user to add.
     */
    public function addUser($username, $update = true)
    {
        $this->data['users'][$username] = 1;
        $this->_auditLog[$username] = 'addUser';
        if ($update && $this->_groupOb->exists($this->getName())) {
            return $this->save();
        }
    }

    /**
     * Removes a user from this group, and makes sure that the backend
     * is updated as well.
     *
     * @param string $username The user to remove.
     */
    public function removeUser($username, $update = true)
    {
        unset($this->data['users'][$username]);
        $this->_auditLog[$username] = 'deleteUser';
        if ($update) {
            return $this->save();
        }
    }

    /**
     * Get a list of every user that is a part of this group
     * (and only this group)
     *
     * @return array  The user list.
     */
    public function listUsers()
    {
        return $this->_groupOb->listUsers($this->getId());
    }

    /**
     * Get a list of every user that is a part of this group and
     * any of it's subgroups
     *
     * @return array  The complete user list.
     */
    public function listAllUsers()
    {
        return $this->_groupOb->listAllUsers($this->getId());
    }

    /**
     * Get all the users recently added or removed from the group.
     */
    public function getAuditLog()
    {
        return $this->_auditLog;
    }

    /**
     * Clears the audit log. To be called after group update.
     */
    public function clearAuditLog()
    {
        $this->_auditLog = array();
    }

    /**
     * Map this object's attributes from the data array into a format
     * that we can store in the attributes storage backend.
     *
     * @return array  The attributes array.
     */
    protected function _toAttributes()
    {
        // Default to no attributes.
        $attributes = array();

        // Loop through all users, if any.
        if (isset($this->data['users']) && is_array($this->data['users']) && count($this->data['users'])) {
            foreach ($this->data['users'] as $user => $active) {
                $attributes[] = array('name' => 'user',
                                      'key' => $user,
                                      'value' => $active);
            }
        }
        $attributes[] = array('name' => 'email',
                              'key' => '',
                              'value' => $this->get('email'));

        return $attributes;
    }

    /**
     * Take in a list of attributes from the backend and map it to our
     * internal data array.
     *
     * @param array $attributes  The list of attributes from the
     *                           backend (attribute name, key, and value).
     */
    protected function _fromAttributes($attributes)
    {
        // Initialize data array.
        $this->data['users'] = array();

        foreach ($attributes as $attr) {
            if ($attr['name'] == 'user') {
                $this->data['users'][$attr['key']] = $attr['value'];
            } else {
                $this->data[$attr['name']] = $attr['value'];
            }
        }
    }

}
