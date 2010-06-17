<?php
/**
 * Extension of the Horde_Share_Object class for storing share information in
 * the sql driver.
 *
 * @author  Duck <duck@obala.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Horde_Share
 */
class Horde_Share_Object_Sql_Hierarchical extends Horde_Share_Object_Sql
{
    /**
     * Constructor. This is here primarily to make calling the parent
     * constructor(s) from any subclasses cleaner.
     *
     * @param unknown_type $data
     * @return Horde_Share_Object_sql_hierarchical
     */
    public function __construct($data)
    {
        if (!isset($data['share_parents'])) {
            $data['share_parents'] = null;
        }
        parent::__construct($data);
    }

    public function inheritPermissions()
    {
        throw new Horde_Share_Exception('Not implemented.');
    }

    /**
     * Return a count of the number of children this share has
     *
     * @param integer $perm  A Horde_Perms::* constant
     * @param boolean $allLevels  Count grandchildren or just children
     *
     * @return mixed  The number of child shares || PEAR_Error
     */
    public function countChildren($perm = Horde_Perms::SHOW, $allLevels = true)
    {
        return $this->_shareOb->countShares($GLOBALS['registry']->getAuth(), $perm, null, $this, $allLevels);
    }

    /**
     * Get all children of this share.
     *
     * @param int $perm           Horde_Perms::* constant. If NULL will return
     *                            all shares regardless of permissions.
     * @param boolean $allLevels  Return all levels.
     *
     * @return mixed  An array of Horde_Share_Object objects || PEAR_Error
     */
    public function getChildren($perm = Horde_Perms::SHOW, $allLevels = true)
    {
        return $this->_shareOb->listShares($GLOBALS['registry']->getAuth(), $perm, null, 0, 0,
             null, 1, $this, $allLevels, is_null($perm));

    }

    /**
     * Returns a child's direct parent
     *
     * @return mixed  The direct parent Horde_Share_Object or PEAR_Error
     */
    public function getParent()
    {
        return $this->_shareOb->getParent($this);
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
        while (is_a($share, 'Horde_Share_Object')) {
            $parents[] = $share;
            $share = $share->getParent();
        }
        return array_reverse($parents);
    }

    /**
     * Set the parent object for this share.
     *
     * @param mixed $parent    A Horde_Share object or share id for the parent.
     *
     * @return mixed  true || PEAR_Error
     */
    public function setParent($parent)
    {
        if (!is_null($parent) && !is_a($parent, 'Horde_Share_Object')) {
            $parent = $this->_shareOb->getShareById($parent);
            if ($parent instanceof PEAR_Error) {
                Horde::logMessage($parent, 'ERR');
                throw new Horde_Share_Exception($parent->getMessage());
            }
        }

        /* If we are an existing share, check for any children */
        if ($this->getId()) {
            $children = $this->_shareOb->listShares(
                $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT, null, 0, 0, null, 0,
                $this->getId());
        } else {
            $children = array();
        }

        /* Can't set a child share as a parent */
        if (!empty($parent) && in_array($parent->getId(), array_keys($children))) {
            throw new Horde_Share_Exception('Cannot set an existing child as the parent');
        }

        if (!is_null($parent)) {
            $parent_string = $parent->get('parents') . ':' . $parent->getId();
        } else {
            $parent_string = null;
        }
        $this->data['share_parents'] = $parent_string;
        $query = $this->_shareOb->getWriteDb()->prepare('UPDATE ' . $this->_shareOb->getTable() . ' SET share_parents = ? WHERE share_id = ?', null, MDB2_PREPARE_MANIP);
        $result = $query->execute(array($this->data['share_parents'], $this->getId()));
        $query->free();
        if ($result instanceof PEAR_Error) {
            throw new Horde_Share_Exception($result->getMessage());
        }

        /* Now we can reset the children's parent */
        foreach($children as $child) {
            $child->setParent($this);
        }

        return true;
    }

    /**
     * Returns the permission of this share.
     *
     * @return Horde_Perms_Permission  Permission object that represents the
     *                               permissions on this share.
     */
    public function getPermission()
    {
        $perm = new Horde_Perms_Permission('');
        $perm->data = isset($this->data['perm'])
            ? $this->data['perm']
            : array();

        return $perm;
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
        if ($attribute == 'owner' || $attribute == 'parents') {
            return $this->data['share_' . $attribute];
        } elseif (isset($this->data['attribute_' . $attribute])) {
            return $this->data['attribute_' . $attribute];
        } else {
            return null;
        }
    }

    /**
     * Hierarchical shares do not have share names.
     *
     * @return unknown
     */
    protected function _getName()
    {
        return '';
    }

}

