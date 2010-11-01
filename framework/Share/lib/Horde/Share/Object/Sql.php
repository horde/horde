<?php
/**
 * Extension of the Horde_Share_Object class for storing share information in
 * the sql driver.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Share
 */
class Horde_Share_Object_Sql extends Horde_Share_Object
{
    /**
     * The actual storage object that holds the data.
     *
     * @TODO: Check visibility - should be protected/private
     * @var mixed
     */
    public $data = array();

    /**
     * Constructor.
     *
     * @param array $data Share data array.
     */
    public function __construct($data)
    {
        if (!isset($data['perm']) || !is_array($data['perm'])) {
            $this->data['perm'] = array(
                'users' => array(),
                'type' => 'matrix',
                'default' => isset($data['perm_default'])
                    ? (int)$data['perm_default'] : 0,
                'guest' => isset($data['perm_guest'])
                    ? (int)$data['perm_guest'] : 0,
                'creator' => isset($data['perm_creator'])
                    ? (int)$data['perm_creator'] : 0,
                'groups' => array());

            unset($data['perm_creator'], $data['perm_guest'],
                  $data['perm_default']);
        }
        $this->data = array_merge($data, $this->data);
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     *
     * @return mixed  True if setting the attribute did succeed, a PEAR_Error
     *                otherwise.
     */
    public function _set($attribute, $value)
    {
        if ($attribute == 'owner') {
            return $this->data['share_owner'] = $value;
        } else {
            return $this->data['attribute_' . $attribute] = $value;
        }
    }

    /**
     * Returns one of the attributes of the object, or null if it isn't
     * defined.
     *
     * @param string $attribute  The attribute to retrieve.
     *
     * @return mixed  The value of the attribute, or an empty string.
     */
    protected function _get($attribute)
    {
        if ($attribute == 'owner') {
            return $this->data['share_owner'];
        } elseif (isset($this->data['attribute_' . $attribute])) {
            return $this->data['attribute_' . $attribute];
        }
    }

    /**
     * Returns the ID of this share.
     *
     * @return string  The share's ID.
     */
    protected function _getId()
    {
        return isset($this->data['share_id']) ? $this->data['share_id'] : null;
    }

    /**
     * Returns the name of this share.
     *
     * @return string  The share's name.
     */
    protected function _getName()
    {
        return $this->data['share_name'];
    }

    /**
     * Saves the current attribute values.
     */
    protected function _save()
    {
        $db = $this->_shareOb->getWriteDb();
        $table = $this->_shareOb->getTable();

        $fields = array();
        $params = array();

        foreach ($this->_shareOb->toDriverCharset($this->data) as $key => $value) {
            if ($key != 'share_id' && $key != 'perm' && $key != 'share_flags') {
                $fields[] = $key;
                $params[] = $value;
            }
        }

        $fields[] = 'perm_creator';
        $params[] = isset($this->data['perm']['creator']) ? (int)$this->data['perm']['creator'] : 0;

        $fields[] = 'perm_default';
        $params[] = isset($this->data['perm']['default']) ? (int)$this->data['perm']['default'] : 0;

        $fields[] = 'perm_guest';
        $params[] = isset($this->data['perm']['guest']) ? (int)$this->data['perm']['guest'] : 0;

        $fields[] = 'share_flags';
        $flags = 0;
        if (!empty($this->data['perm']['users'])) {
            $flags |= Horde_Share_Sql::SQL_FLAG_USERS;
        }
        if (!empty($this->data['perm']['groups'])) {
            $flags |= Horde_Share_Sql::SQL_FLAG_GROUPS;
        }
        $params[] = $flags;

        if (empty($this->data['share_id'])) {
            $share_id = $db->nextId($table);
            if ($share_id instanceof PEAR_Error) {
                Horde::logMessage($share_id, 'ERR');
                throw new Horde_Share_Exception($share_id->getMessage());
            }

            $this->data['share_id'] = $share_id;
            $fields[] = 'share_id';
            $params[] = $this->data['share_id'];

            $query = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (?' . str_repeat(', ?', count($fields) - 1) . ')';
        } else {
            $query = 'UPDATE ' . $table . ' SET ' . implode(' = ?, ', $fields) . ' = ? WHERE share_id = ?';
            $params[] = $this->data['share_id'];
        }
        $stmt = $db->prepare($query, null, MDB2_PREPARE_MANIP);
        if ($stmt instanceof PEAR_Error) {
            Horde::logMessage($stmt, 'ERR');
            throw new Horde_Share_Exception($stmt->getMessage());
        }
        $result = $stmt->execute($params);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Share_Exception($result->getMessage());
        }
        $stmt->free();

        // Update the share's user permissions
        $stmt = $db->prepare('DELETE FROM ' . $table . '_users WHERE share_id = ?', null, MDB2_PREPARE_MANIP);
        if ($stmt instanceof PEAR_Error) {
            Horde::logMessage($stmt, 'ERR');
            throw new Horde_Share_Exception($stmt->getMessage());
        }
        $result = $stmt->execute(array($this->data['share_id']));
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Share_Exception($result->getMessage());
        }
        $stmt->free();

        if (!empty($this->data['perm']['users'])) {
            $data = array();
            foreach ($this->data['perm']['users'] as $user => $perm) {
                $stmt = $db->prepare('INSERT INTO ' . $table . '_users (share_id, user_uid, perm) VALUES (?, ?, ?)', null, MDB2_PREPARE_MANIP);
                if ($stmt instanceof PEAR_Error) {
                    Horde::logMessage($stmt, 'ERR');
                    throw new Horde_Share_Exception($stmt->getMessage());
                }
                $result = $stmt->execute(array($this->data['share_id'], $user, $perm));
                if ($result instanceof PEAR_Error) {
                    Horde::logMessage($result, 'ERR');
                    throw new Horde_Share_Exception($result->getMessage());
                }
                $stmt->free();
            }
        }

        // Update the share's group permissions
        $stmt = $db->prepare('DELETE FROM ' . $table . '_groups WHERE share_id = ?', null, MDB2_PREPARE_MANIP);
        if ($stmt instanceof PEAR_Error) {
            Horde::logMessage($stmt, 'ERR');
            throw new Horde_Share_Exception($stmt->getMessage());
        }
        $result = $stmt->execute(array($this->data['share_id']));
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Share_Exception($result->getMessage());
        }
        $stmt->free();

        if (!empty($this->data['perm']['groups'])) {
            $data = array();
            foreach ($this->data['perm']['groups'] as $group => $perm) {
                $stmt = $db->prepare('INSERT INTO ' . $table . '_groups (share_id, group_uid, perm) VALUES (?, ?, ?)', null, MDB2_PREPARE_MANIP);
                if ($stmt instanceof PEAR_Error) {
                    Horde::logMessage($stmt, 'ERR');
                    throw new Horde_Share_Exception($stmt->getMessage());
                }
                $result = $stmt->execute(array($this->data['share_id'], $group, $perm));
                if ($result instanceof PEAR_Error) {
                    Horde::logMessage($result, 'ERR');
                    throw new Horde_Share_Exception($result->getMessage());
                }
                $stmt->free();
            }
        }

        return true;
    }

    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    public function hasPermission($userid, $permission, $creator = null)
    {
        if ($userid == $this->data['share_owner']) {
            return true;
        }
        return $this->_shareOb->getPermsObject()->hasPermission($this->getPermission(), $userid, $permission, $creator);
    }

    /**
     * Sets the permission of this share.
     *
     * @param Horde_Perms_Permission $perm  Permission object.
     * @param boolean $update               Should the share be saved
     *                                      after this operation?
     *
     * @TODO: Look at storing the Perm object itself, instead of the data
     *        (Make it easier to inject the perm object instead of instantiating
     *        it in the library).
     *
     * @return boolean  True if no error occured, PEAR_Error otherwise
     */
    public function setPermission($perm, $update = true)
    {
        $this->data['perm'] = $perm->getData();
        if ($update) {
            return $this->save();
        }
        return true;
    }

    /**
     * Returns the permission of this share.
     *
     * @return Horde_Perms_Permission  Permission object that represents the
     *                                 permissions on this share.
     */
    public function getPermission()
    {
        $perm = new Horde_Perms_Permission($this->getName());
        $perm->data = isset($this->data['perm'])
            ? $this->data['perm']
            : array();

        return $perm;
    }

}
