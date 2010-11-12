<?php

require_once 'Horde/DataTree.php';

/**
 * Horde_Share_datatree:: provides the datatree backend for the horde share
 * driver.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 * Copyright 2002-2007 Infoteck Internet <webmaster@infoteck.qc.ca>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @author  Mike Cochrame <mike@graftonhall.co.nz>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Share
 */
class Horde_Share_Datatree extends Horde_Share
{
    /**
     * The Horde_Share_Object subclass to instantiate objects as
     *
     * @var string
     */
    protected $_shareObject = 'Horde_Share_Object_Datatree';

    /**
     * Pointer to a DataTree instance to manage/store shares
     *
     * @var DataTree
     */
    protected $_datatree;

    /**
     * Initializes the object.
     *
     * @throws Horde_Exception
     */
    public function __wakeup()
    {
        // TODO: Remove GLOBAL config access
        if (empty($GLOBALS['conf']['datatree']['driver'])) {
            throw new Horde_Exception('You must configure a DataTree backend to use Shares.');
        }

        $driver = $GLOBALS['conf']['datatree']['driver'];
        $this->_datatree = &DataTree::singleton(
            $driver,
            array_merge(Horde::getDriverConfig('datatree', $driver),
                        array('group' => 'horde.shares.' . $this->_app))
        );

        foreach (array_keys($this->_cache) as $name) {
            if (!is_a($this->_datatree, 'PEAR_Error')) {
                $this->_cache[$name]->setShareOb($this);
                $this->_cache[$name]->datatreeObject->setDataTree($this->_datatree);
            }
        }

        parent::__wakeup();
    }

    /**
     * Returns a Horde_Share_Object_datatree object corresponding to the given
     * share name, with the details retrieved appropriately.
     *
     * @param string $name  The name of the share to retrieve.
     *
     * @return Horde_Share_Object_datatree  The requested share.
     * @throws Horde_Share_Exception
     */
    public function _getShare($name)
    {
        $datatreeObject = $this->_datatree->getObject($name, 'DataTreeObject_Share');
        if ($datatreeObject instanceof PEAR_Error) {
            throw new Horde_Share_Exception($datatreeObject->getMessage());
        }
        $share = $this->_createObject($datatreeObject);

        return $share;
    }

    /**
     * Returns a Horde_Share_Object_datatree object corresponding to the given
     * unique ID, with the details retrieved appropriately.
     *
     * @param string $cid  The id of the share to retrieve.
     *
     * @return Horde_Share_Object_datatree  The requested share.
     * @throws Horde_Share_Exception
     */
    protected function _getShareById($id)
    {
        $datatreeObject = $this->_datatree->getObjectById($id, 'DataTreeObject_Share');
        if (is_a($datatreeObject, 'PEAR_Error')) {
            throw new Horde_Share_Exception($datatreeObject->getMessage());
        }
        $share = $this->_createObject($datatreeObject);

        return $share;
    }

    /**
     * Returns an array of Horde_Share_Object_datatree objects corresponding
     * to the given set of unique IDs, with the details retrieved
     * appropriately.
     *
     * @param array $cids  The array of ids to retrieve.
     *
     * @return array  The requested shares.
     * @throws Horde_Share_Exception
     */
    protected function _getShares($ids)
    {
        $shares = array();
        $objects = &$this->_datatree->getObjects($ids, 'DataTreeObject_Share');
        if (is_a($objects, 'PEAR_Error')) {
            throw new Horde_Share_Exception($objects->getMessage());
        }
        foreach (array_keys($objects) as $key) {
            if (is_a($objects[$key], 'PEAR_Error')) {
                return $objects[$key];
            }
            $shares[$key] = $this->_createObject($objects[$key]);
        }
        return $shares;
    }

    /**
     * Lists *all* shares for the current app/share, regardless of
     * permissions.
     *
     * @return array  All shares for the current app/share.
     * @throws Horde_Share_Exception
     */
    function _listAllShares()
    {
        $sharelist = $this->_datatree->get(DATATREE_FORMAT_FLAT, DATATREE_ROOT, true);
        if ($sharelist instanceof PEAR_Error) {
            throw new Horde_Share_Exception($sharelist->getMessage());
        }
        if (!count($sharelist)) {
            return $sharelist;
        }
        unset($sharelist[DATATREE_ROOT]);

        return $this->getShares(array_keys($sharelist));
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param integer $perm      The level of permissions required.
     * @param mixed $attributes  Restrict the shares counted to those
     *                           matching $attributes. An array of
     *                           attribute/values pairs or a share owner
     *                           username.
     *
     * @return array  The shares the user has access to.
     * @throws Horde_Share_Exception
     */
    function _listShares($userid, $perm = Horde_Perms::SHOW,
                         $attributes = null, $from = 0, $count = 0,
                         $sort_by = null, $direction = 0)
    {
        $key = serialize(array($userid, $perm, $attributes));
        if (empty($this->_listCache[$key])) {
            $criteria = $this->getShareCriteria($userid, $perm, $attributes);
            $sharelist = $this->_datatree->getByAttributes($criteria,
                                                           DATATREE_ROOT,
                                                           true, 'id', $from,
                                                           $count, $sort_by,
                                                           null, $direction);
            if ($sharelist instanceof PEAR_Error) {
                throw new Horde_Share_Exception($sharelist->getMessage());
            }
            $this->_listCache[$key] = array_keys($sharelist);
        }

        return $this->_listCache[$key];
    }

    /**
     * Returns the number of shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param integer $perm      The level of permissions required.
     * @param mixed $attributes  Restrict the shares counted to those
     *                           matching $attributes. An array of
     *                           attribute/values pairs or a share owner
     *                           username.
     *
     * @return integer  The number of shares
     */
    protected function _countShares($userid, $perm = Horde_Perms::SHOW,
                                    $attributes = null)
    {
        $criteria = $this->getShareCriteria($userid, $perm, $attributes);
        return $this->_datatree->countByAttributes($criteria, DATATREE_ROOT, true, 'id');
    }

    /**
     * Returns a new share object. Share objects should *ALWAYS* be instantiated
     * via the Horde_Share object.
     *
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object_datatree  A new share object.
     */
    protected function _newShare($name)
    {
        if (empty($name)) {
            throw new Horde_Share_Exception('Share names must be non-empty');
        }
        $datatreeObject = new Horde_Share_Object_DataTree_Share($name);
        $datatreeObject->setDataTree($this->_datatree);
        $share = $this->_createObject($datatreeObject);

        return $share;
    }

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with
     * Horde_Share_datatreee::_newShare(), and have any initial details added
     * to it, before this function is called.
     *
     * @param Horde_Share_Object_datatree $share  The new share object.
     */
    protected function _addShare($share)
    {
        return $this->_datatree->add($share->datatreeObject);
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object_datatree $share  The share to remove.
     */
    protected function _removeShare($share)
    {
        return $this->_datatree->remove($share->datatreeObject);
    }

    /**
     * Checks if a share exists in the system.
     *
     * @param string $share  The share to check.
     *
     * @return boolean  True if the share exists.
     */
    protected function _exists($share)
    {
        return $this->_datatree->exists($share);
    }

    /**
     * Returns an array of criteria for querying shares.
     * @access protected
     *
     * @param string  $userid      The userid of the user to check access for.
     * @param integer $perm        The level of permissions required.
     * @param mixed   $attributes  Restrict the shares returned to those who
     *                             have these attribute values.
     *
     * @return array  The criteria tree for fetching this user's shares.
     */
    public function getShareCriteria($userid, $perm = Horde_Perms::SHOW,
                                         $attributes = null)
    {
        if (!empty($userid)) {
            $criteria = array(
                'OR' => array(
                    // (owner == $userid)
                    array(
                        'AND' => array(
                            array('field' => 'name', 'op' => '=', 'test' => 'owner'),
                            array('field' => 'value', 'op' => '=', 'test' => $userid))),

                    // (name == perm_users and key == $userid and val & $perm)
                    array(
                        'AND' => array(
                            array('field' => 'name', 'op' => '=', 'test' => 'perm_users'),
                            array('field' => 'key', 'op' => '=', 'test' => $userid),
                            array('field' => 'value', 'op' => '&', 'test' => $perm))),

                    // (name == perm_creator and val & $perm)
                    array(
                        'AND' => array(
                            array('field' => 'name', 'op' => '=', 'test' => 'perm_creator'),
                            array('field' => 'value', 'op' => '&', 'test' => $perm))),

                    // (name == perm_default and val & $perm)
                    array(
                        'AND' => array(
                            array('field' => 'name', 'op' => '=', 'test' => 'perm_default'),
                            array('field' => 'value', 'op' => '&', 'test' => $perm)))));

            // If the user has any group memberships, check for those also.
            // @TODO: inject
            try {
                $group = $GLOBALS['injector']->getInstance('Horde_Group');
                $groups = $group->getGroupMemberships($userid, true);
                if ($groups) {
                    // (name == perm_groups and key in ($groups) and val & $perm)
                    $criteria['OR'][] = array(
                        'AND' => array(
                            array('field' => 'name', 'op' => '=', 'test' => 'perm_groups'),
                            array('field' => 'key', 'op' => 'IN', 'test' => array_keys($groups)),
                            array('field' => 'value', 'op' => '&', 'test' => $perm)));
                }
            } catch (Horde_Group_Exception $e) {}
        } else {
            $criteria = array(
                'AND' => array(
                     array('field' => 'name', 'op' => '=', 'test' => 'perm_guest'),
                     array('field' => 'value', 'op' => '&', 'test' => $perm)));
        }

        if (is_array($attributes)) {
            // Build attribute/key filter.
            foreach ($attributes as $key => $value) {
                $criteria = array(
                    'AND' => array(
                        $criteria,
                        array(
                            'JOIN' => array(
                                'AND' => array(
                                    array('field' => 'name', 'op' => '=', 'test' => $key),
                                    array('field' => 'value', 'op' => '=', 'test' => $value))))));
            }
        } elseif (!is_null($attributes)) {
            // Restrict to shares owned by the user specified in the
            // $attributes string.
            $criteria = array(
                'AND' => array(
                    $criteria,
                    array(
                        'JOIN' => array(
                            array('field' => 'name', 'op' => '=', 'test' => 'owner'),
                            array('field' => 'value', 'op' => '=', 'test' => $attributes)))));
        }

        return $criteria;
    }

}
