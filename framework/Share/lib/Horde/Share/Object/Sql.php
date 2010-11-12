<?php
/**
 * Extension of the Horde_Share_Object class for storing share information in
 * the sql driver.
 *
 * @author  Duck <duck@obala.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Share
 */
class Horde_Share_Object_Sql extends Horde_Share_Object
{
    /** Serializable version **/
    const VERSION = 1;

    /**
     * The actual storage object that holds the data.
     *
     * @TODO: Check visibility - should be protected/private
     * @var array
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
     * Serialize this object. You *MUST* call setShareOb() after unserializing.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        $data = array(
            self::VERSION,
            $this->data,
        );

        return serialize($data);
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * You *MUST* call setShareOb() after unserializing.
     *
     * @param string $data  The serialized data.
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->data = $data[1];
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     *
     * @return boolean
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
        $db = $this->_shareOb->getStorage();
        $table = $this->_shareOb->getTable();

        $fields = array();
        $params = array();

        // Build the parameter arrays for the sql statement.
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

        // Insert new share record, or update existing
        if (empty($this->data['share_id'])) {
            $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (?' . str_repeat(', ?', count($fields) - 1) . ')';
            try {
                $this->data['share_id'] = $db->insert($sql, $params);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Share_Exception($e);
            }
        } else {
            $sql = 'UPDATE ' . $table . ' SET ' . implode(' = ?, ', $fields) . ' = ? WHERE share_id = ?';
            $params[] = $this->data['share_id'];
            try {
                $db->update($sql, $params);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Share_Exception($e);
            }
        }

        // Update the share's user permissions
        $db->delete('DELETE FROM ' . $table . '_users WHERE share_id = ?', array($this->data['share_id']));
        if (!empty($this->data['perm']['users'])) {
            $data = array();
            foreach ($this->data['perm']['users'] as $user => $perm) {
                $db->insert('INSERT INTO ' . $table . '_users (share_id, user_uid, perm) VALUES (?, ?, ?)', array($this->data['share_id'], $user, $perm));
            }
        }

        // Update the share's group permissions
        $db->delete('DELETE FROM ' . $table . '_groups WHERE share_id = ?', array($this->data['share_id']));
        if (!empty($this->data['perm']['groups'])) {
            $data = array();
            foreach ($this->data['perm']['groups'] as $group => $perm) {
                $db->insert('INSERT INTO ' . $table . '_groups (share_id, group_uid, perm) VALUES (?, ?, ?)', array($this->data['share_id'], $group, $perm));
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
     * @return boolean
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
