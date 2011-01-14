<?php
/**
 * Extension of the Horde_Share_Object class for storing share information in
 * the Sqlng driver.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Share
 */
class Horde_Share_Object_Sqlng extends Horde_Share_Object_Sql
{
    /**
     * Serializable version.
     */
    const VERSION = 1;

    /**
     * A list of available permission.
     *
     * This is necessary to unset certain permission when updating existing
     * share objects.
     *
     * @param array
     */
    public $availablePermissions = array();

    /**
     * Constructor.
     *
     * @param array $data Share data array.
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->_setAvailablePermissions();
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
            $this->data,
            $this->_shareCallback,
            $this->availablePermissions,
        ));
    }

    /**
     * Reconstruct the object from serialized data.
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
        if (empty($data[2])) {
            throw new Exception('Missing callback for Horde_Share_Object unserializing');
        }
        $this->_shareCallback = $data[2];
        $this->availablePermissions = $data[3];
    }

    /**
     * Saves the current attribute values.
     */
    protected function _save()
    {
        $db = $this->getShareOb()->getStorage();
        $table = $this->getShareOb()->getTable();

        // Build the parameter arrays for the sql statement.
        $fields = $params = array();
        foreach ($this->getShareOb()->toDriverCharset($this->data) as $key => $value) {
            if ($key != 'share_id' && $key != 'perm' && $key != 'share_flags') {
                $fields[] = $key;
                $params[] = $value;
            }
        }

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
            foreach ($this->data['perm'] as $base => $perms) {
                if ($base == 'type' || $base == 'users' || $base == 'groups') {
                    continue;
                }
                foreach (Horde_Share_Sqlng::convertBitmaskToArray($perms) as $perm) {
                    $fields[] = 'perm_' . $base . '_' . $perm;
                    $params[] = true;
                }
            }
            $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (?' . str_repeat(', ?', count($fields) - 1) . ')';
            try {
                $this->data['share_id'] = $db->insert($sql, $params);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Share_Exception($e);
            }
        } else {
            foreach ($this->data['perm'] as $base => $perms) {
                if ($base == 'type' || $base == 'users' || $base == 'groups') {
                    continue;
                }
                $perms = array_flip(Horde_Share_Sqlng::convertBitmaskToArray($perms));
                foreach ($this->availablePermissions as $perm) {
                    $fields[] = 'perm_' . $base . '_' . $perm;
                    $params[] = isset($perms[$perm]) ? true : false;
                }
            }
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
            foreach ($this->data['perm']['users'] as $user => $perms) {
                $fields = $params = array();
                foreach (Horde_Share_Sqlng::convertBitmaskToArray($perms) as $perm) {
                    $fields[] = 'perm_' . $perm;
                    $params[] = true;
                }
                if (!$fields) {
                    continue;
                }
                array_unshift($params, $user);
                array_unshift($params, $this->data['share_id']);
                $db->insert('INSERT INTO ' . $table . '_users (share_id, user_uid, ' . implode(', ', $fields) . ') VALUES (?, ?' . str_repeat(', ?', count($fields)) . ')', $params);
            }
        }

        // Update the share's group permissions
        $db->delete('DELETE FROM ' . $table . '_groups WHERE share_id = ?', array($this->data['share_id']));
        if (!empty($this->data['perm']['groups'])) {
            $data = array();
            foreach ($this->data['perm']['groups'] as $group => $perms) {
                $fields = $params = array();
                foreach (Horde_Share_Sqlng::convertBitmaskToArray($perms) as $perm) {
                    $fields[] = 'perm_' . $perm;
                    $params[] = true;
                }
                if (!$fields) {
                    continue;
                }
                array_unshift($params, $group);
                array_unshift($params, $this->data['share_id']);
                $db->insert('INSERT INTO ' . $table . '_groups (share_id, group_uid, ' . implode(', ', $fields) . ') VALUES (?, ?' . str_repeat(', ?', count($fields)) . ')', $params);
            }
        }

        return true;
    }

    /**
     * Sets the permission of this share.
     *
     * @param Horde_Perms_Permission $perm  Permission object.
     * @param boolean $update               Should the share be saved
     *                                      after this operation?
     */
    public function setPermission($perm, $update = true)
    {
        parent::setPermission($perm, $update);
        $this->_setAvailablePermissions();
    }

    /**
     * Populates the $availablePermissions property with all seen permissions.
     *
     * This is necessary because the share tables might be extended with
     * arbitrary permissions.
     */
    protected function _setAvailablePermissions()
    {
        $available = array();
        foreach ($this->availablePermissions as $perm) {
            $available[$perm] = true;
        }
        foreach ($this->data['perm'] as $base => $perms) {
            if ($base == 'type') {
                continue;
            }
            if ($base != 'users' && $base != 'groups') {
                $perms = array($perms);
            }
            foreach ($perms as $subperms) {
                foreach (Horde_Share_Sqlng::convertBitmaskToArray($subperms) as $perm) {
                    $available[$perm] = true;
                }
            }
        }
        $this->availablePermissions = array_keys($available);
    }
}
