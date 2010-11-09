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
     * Constructor.
     *
     * @param array $data
     *
     * @return Horde_Share_Object_sql_hierarchical
     */
    public function __construct($data)
    {
        if (!isset($data['share_parents'])) {
            $data['share_parents'] = null;
        }
        parent::__construct($data);
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
        return $this->_shareOb->countShares($user, $perm, null, $this, $allLevels);
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
        return $this->_shareOb->listShares(
            $user, array('perm' => $perm,
                         'direction' => 1,
                         'parent' => $this,
                         'all_levels' => $allLevels,
                         'ignore_perms' => is_null($perm)));
    }

    /**
     * Returns a child's direct parent
     *
     * @return Horde_Share_Object The direct parent Horde_Share_Object
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
        while ($share instanceof Horde_Share_Object) {
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
     * @return boolean
     */
    public function setParent($parent)
    {
        if (!is_null($parent) && !is_a($parent, 'Horde_Share_Object')) {
            $parent = $this->_shareOb->getShareById($parent);
        }

        /* If we are an existing share, check for any children */
        if ($this->getId()) {
            $children = $this->_shareOb->listShares(null,
                array('perm' => Horde_Perms::EDIT,
                      'parent' => $this,
                      'all_levels' => true,
                      'ignore_perms' => true));
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
        $sql = 'UPDATE ' . $this->_shareOb->getTable() . ' SET share_parents = ? WHERE share_id = ?';
        try {
            $this->_shareOb->getStorage()->update($sql, array($this->data['share_parents'], $this->getId()));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
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
     *                                 permissions on this share.
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
