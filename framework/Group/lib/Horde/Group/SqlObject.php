<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_SqlObject extends Horde_Group_DataTreeObject
{
    /**
     * The unique name of this object.
     * These names have the same requirements as other object names - they must
     * be unique, etc.
     *
     * @var string
     */
    public $name;

    /**
     * The unique name of this object.
     * These names have the same requirements as other object names - they must
     * be unique, etc.
     *
     * @var integer
     */
    public $id;

    /**
     * Key-value hash that will be serialized.
     *
     * @see getData()
     * @var array
     */
    public $data = array();

    /**
     * Constructor.
     *
     * @param string $name  The name of the group.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the ID of this object.
     *
     * @return string  The object's ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the name of this object.
     *
     * @return string The object name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets one of the attributes of the object, or null if it isn't defined.
     *
     * @param string $attribute  The attribute to get.
     *
     * @return mixed  The value of the attribute, or null.
     */
    public function get($attribute)
    {
        return isset($this->data[$attribute])
            ? $this->data[$attribute]
            : null;
    }

    /**
     * Sets one of the attributes of the object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     */
    public function set($attribute, $value)
    {
        $this->data[$attribute] = $value;
    }

    /**
     * Save group
     *
     * @throws Horde_Group_Exception
     */
    public function save()
    {
        if (isset($this->data['email'])) {
            $query = 'UPDATE horde_groups SET group_email = ? WHERE group_uid = ?';
            try {
                $this->_groupOb->db->update($query, array($this->data['email'], $this->id));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Group_Exception($e);
            }
        }

        $query = 'DELETE FROM horde_groups_members WHERE group_uid = ?';

        try {
            $this->_groupOb->db->delete($query, array($this->id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        if (!empty($this->data['users'])) {
            $query = 'INSERT INTO horde_groups_members (group_uid, user_uid)' .
                     ' VALUES (?, ?)';
            foreach ($this->data['users'] as $user) {
                try {
                    $this->db->insert($query, array(intval($this->id), $user));
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Group_Exception($e);
                }
            }
        }
    }

}
