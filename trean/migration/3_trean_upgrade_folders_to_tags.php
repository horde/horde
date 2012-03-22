<?php
/**
 * Run the changes to migrate from folders to tags.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Trean
 */
class TreanUpgradeFoldersToTags extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        // See if there's anything to do first.
        $num_bookmarks = $this->_connection->selectValue('SELECT COUNT(*) FROM trean_bookmarks');
        if (!$num_bookmarks) {
            $this->announce('No bookmarks to migrate.');
            return;
        }
        $this->announce("Migrating $num_bookmarks bookmarks.");

        $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the Content application is installed.');
        }
        $user_mgr = $GLOBALS['injector']->getInstance('Content_Users_Manager');
        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('bookmark'));
        $type_ids = array('bookmark' => (int)$types[0]);
        $tagger = $GLOBALS['injector']->getInstance('Content_Tagger');

        $driver = $GLOBALS['conf']['datatree']['driver'];
        $config = Horde::getDriverConfig('datatree', $GLOBALS['conf']['datatree']['driver']);
        $datatree = Horde_DataTree::singleton($driver, array_merge($config, array('group' => 'horde.shares.trean')));
        if ($datatree instanceof PEAR_Error) {
            throw new Horde_Exception("Can't find horde.shares.trean DataTree group containing folder information.");
        }
        $users = $datatree->getById(DATATREE_FORMAT_FLAT, DATATREE_ROOT, 'horde.shares.trean', true, 1);
        foreach ($users as $id => $user) {
            if ($id != DATATREE_ROOT) {
                $this->announce("Migrating folders for $user");
                $stub = new TreanFolderStub($datatree);
                $folders = $stub->getFolders($user);
                foreach ($folders as $folder) {
                    // Skip folders with no bookmarks and that this user doesn't own.
                    if ($folder->get('owner') != $user) { continue; }
                    if (!$this->_connection->selectValue('SELECT COUNT(*) FROM trean_bookmarks WHERE folder_id = ?', array($folder->getId()))) { continue; }

                    $userId = current($user_mgr->ensureUsers($user));
                    $folderToUser[$folder->getId()] = $user;
                    $this->_connection->update('UPDATE trean_bookmarks SET user_id = ? WHERE folder_id = ?', array($userId, $folder->getId()));

                    if ($folder->getName() != $user) {
                        $folderToTag[$folder->getId()] = array($folder->get('name'));
                        $p = $folder;
                        while ($p->getParent()) {
                            $p = $stub->getFolder($p->getParent());
                            if ($p->getName() != $user) {
                                $folderToTag[$folder->getId()][] = $p->get('name');
                            }
                        }
                    }
                }
            }
        }

        $this->announce('Tagging bookmarks');
        foreach ($folderToTag as $folderId => $tags) {
            if (!$tags) { continue; }

            $bookmarkIds = $this->_connection->selectValues('SELECT bookmark_id FROM trean_bookmarks WHERE folder_id = ?', array($folderId));
            if ($bookmarkIds) {
                foreach ($bookmarkIds as $bookmarkId) {
                    $tagger->tag($folderToUser[$folderId], array('type' => 'bookmark', 'object' => (string)$bookmarkId), $tags);
                }
            }
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        // Not supported. One-way change. Also not destructive on its own.
    }
}

class TreanFolderStub
{
    /**
     * Pointer to a Horde_DataTree instance to manage/store shares
     *
     * @var Horde_DataTree
     */
    var $_datatree;

    /**
     * A cache of all shares that have been retrieved, so we don't hit the
     * backend again and again for them.
     *
     * @var array
     */
    var $_cache = array();

    /**
     * Id-name-map of already cached share objects.
     *
     * @var array
     */
    var $_shareMap = array();

    /**
     * Cache used for listFolders/getFolders().
     *
     * @var array
     */
    var $_listcache = array();

    /**
     * Caches the number of share matching certain criteria.
     *
     * @see countShares()
     * @var array
     */
    var $_counts = array();

    /**
     * A list of objects that we're currently sorting, for reference during the
     * sorting algorithm.
     *
     * @var array
     */
    var $_sortList;

    /**
     * Constructor.
     */
    public function __construct($datatree)
    {
        $this->_datatree = $datatree;
    }

    /**
     * Returns an array of TreanFolderObjectStub objects corresponding to the
     * given set of unique IDs, with the details retrieved appropriately.
     *
     * @param array $cids  The array of ids to retrieve.
     *
     * @return array  The requested shares.
     */
    function getShares($cids)
    {
        $all_shares = array();
        $missing_ids = array();
        foreach ($cids as $cid) {
            if (isset($this->_shareMap[$cid])) {
                $all_shares[$this->_shareMap[$cid]] = $this->_cache[$this->_shareMap[$cid]];
            } else {
                $missing_ids[] = $cid;
            }
        }

        if (count($missing_ids)) {
            $shares = $this->_datatree->getObjects($missing_ids, 'TreanFolderObjectStub');
            if (is_a($shares, 'PEAR_Error')) {
                return $shares;
            }

            $keys = array_keys($shares);
            foreach ($keys as $key) {
                if (is_a($shares[$key], 'PEAR_Error')) {
                    return $shares[$key];
                }

                $shares[$key]->setShareOb($this);
                $all_shares[$key] = $shares[$key];
                $this->_cache[$key] = $shares[$key];
                $this->_shareMap[$shares[$key]->getId()] = $key;
            }
        }

        return $all_shares;
    }

    /**
     * Returns the folder ids that $userid has access to.
     *
     * @param string  $userid       The userid of the user to check access for.
     * @param integer $perm         The level of permissions required.
     * @param string  $parent       The parent share to start searching at.
     * @param boolean $allLevels    Return all levels, or just the direct
     *                              children of $parent? Defaults to all
     *                              levels.
     *
     * @return array The folder ids $userid has access to.
     */
    function listFolders($userid, $perm = Horde_Perms::SHOW, $parent = null, $allLevels = true)
    {
        if (is_null($parent)) {
            $parent = DATATREE_ROOT;
        }

        $key = serialize(array($userid, $perm, $parent, $allLevels));
        if (empty($this->_listCache[$key])) {
            $criteria = $this->_getShareCriteria($userid, $perm);
            $sharelist = $this->_datatree->getByAttributes(
                $criteria, $parent, $allLevels, 'id', 0, 0, 'name'
            );
            if (is_a($sharelist, 'PEAR_Error')) {
                return $sharelist;
            }
            $this->_listCache[$key] = array_keys($sharelist);
        }

        return $this->_listCache[$key];
    }

    /**
     * Returns an array of all folders that $userid has access to.
     *
     * @param string  $userid       The userid of the user to check access for.
     * @param integer $perm         The level of permissions required.
     * @param string  $parent       The parent share to start searching at.
     * @param boolean $allLevels    Return all levels, or just the direct
     *                              children of $parent? Defaults to all
     *                              levels.
     *
     * @return array  The shares the user has access to.
     */
    function getFolders($userid, $perm = Horde_Perms::SHOW, $parent = null, $allLevels = true)
    {
        $folderIds = $this->listFolders($userid, $perm, $parent, $allLevels);
        if (!count($folderIds) || is_a($folderIds, 'PEAR_Error')) {
            return $folderIds;
        }

        /* Make sure getShares() didn't return an error. */
        $shares = $this->getShares($folderIds);
        if (is_a($shares, 'PEAR_Error')) {
            return $shares;
        }

        $this->_sortList = $shares;
        uasort($shares, array($this, '_sortShares'));
        $this->_sortList = null;
        return $shares;
    }

    /**
     * Returns a TreanFolderObjectStub object corresponding to the given unique
     * ID, with the details retrieved appropriately.
     *
     * @param string $cid  The id of the folder to retrieve.
     *
     * @return TreanFolderObjectStub The requested folder.
     */
    function getFolder($cid)
    {
        if (isset($this->_shareMap[$cid])) {
            $share = $this->_cache[$this->_shareMap[$cid]];
        } else {
            $share = $this->_datatree->getObjectById($cid, 'TreanFolderObjectStub');
            if (!is_a($share, 'PEAR_Error')) {
                $share->setShareOb($this);
                $name = $share->getName();
                $this->_cache[$name] = $share;
                $this->_shareMap[$cid] = $name;
            }
        }

        return $share;
    }

    /**
     * Returns the id of the folder $name.
     *
     * @param string $name  A folder name.
     *
     * @return integer  A folder id.
     */
    function getId($name)
    {
        return $this->_datatree->getId($name);
    }

    /**
     * Utility function to be used with uasort() for sorting arrays of
     * Trean_Bookmarks objects.
     * Example:<code>
     * uasort($list, array('Trean_Bookmarks', '_sortShares'));
     * </code>
     *
     * @access private
     */
    function _sortShares($a, $b)
    {
        $aParts = explode(':', $a->getName());
        $bParts = explode(':', $b->getName());

        $min = min(count($aParts), count($bParts));
        $idA = '';
        $idB = '';
        for ($i = 0; $i < $min; $i++) {
            if ($idA) {
                $idA .= ':';
                $idB .= ':';
            }
            $idA .= $aParts[$i];
            $idB .= $bParts[$i];

            if ($idA != $idB) {
                $curA = isset($this->_sortList[$idA]) ? $this->_sortList[$idA]->get('name') : '';
                $curB = isset($this->_sortList[$idB]) ? $this->_sortList[$idB]->get('name') : '';
                return strnatcasecmp($curA, $curB);
            }
        }

        return count($aParts) > count($bParts);
    }

    /**
     * Returns an array of criteria for querying shares.
     *
     * @param string  $userid      The userid of the user to check access for.
     * @param integer $perm        The level of permissions required.
     *
     * @return array  The criteria tree for fetching this user's shares.
     */
    function _getShareCriteria($userid, $perm = Horde_Perms::SHOW)
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
            $groups = $GLOBALS['injector']
                ->getInstance('Horde_Group')
                ->listGroups($userid);
            if (is_array($groups) && count($groups)) {
                // (name == perm_groups and key in ($groups) and val & $perm)
                $criteria['OR'][] = array(
                    'AND' => array(
                        array('field' => 'name', 'op' => '=', 'test' => 'perm_groups'),
                        array('field' => 'key', 'op' => 'IN', 'test' => array_keys($groups)),
                        array('field' => 'value', 'op' => '&', 'test' => $perm)));
            }
        } else {
            $criteria = array(
                'AND' => array(
                     array('field' => 'name', 'op' => '=', 'test' => 'perm_guest'),
                     array('field' => 'value', 'op' => '&', 'test' => $perm)));
        }

        return $criteria;
    }
}

/**
 * Extension of the Horde_DataTreeObject class for storing bookmark folders.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Trean
 */
class TreanFolderObjectStub extends Horde_DataTreeObject {

    /**
     * The Trean_Bookmarks object which this share came from - needed
     * for updating data in the backend to make changes stick, etc.
     *
     * @var Trean_Bookmarks
     */
    var $_shareOb;

    /**
     * The TreanFolderObjectStub constructor. Just makes sure to call the parent
     * constructor so that the share's name is set properly.
     *
     * @param string $id  The id of the share.
     */
    function TreanFolderObjectStub($id)
    {
        parent::__construct($id);
        if (is_null($this->data)) {
            $this->data = array();
        }
    }

    /**
     * Associates a Trean_Bookmarks object with this share.
     *
     * @param Trean_Bookmarks $shareOb The Trean_Bookmarks object.
     */
    function setShareOb($shareOb)
    {
        $this->_shareOb = $shareOb;
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
    function hasPermission($userid, $permission, $creator = null)
    {
        if ($userid == $this->get('owner')) {
            return true;
        }

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        return $perms->hasPermission($this->getPermission(), $userid, $permission, $creator);
    }

    /**
     * TODO
     *
     * @param TODO
     * @param boolean $update  TODO
     *
     * @return TODO
     */
    function setPermission($perm, $update = true)
    {
        $this->data['perm'] = $perm->getData();
        if ($update) {
            return $this->save();
        }
        return true;
    }

    /**
     * TODO
     *
     * @return Horde_Perms_Permission
     */
    function getPermission()
    {
        $perm = new Horde_Perms_Permission($this->getName());
        $perm->data = isset($this->data['perm']) ? $this->data['perm'] : array();

        return $perm;
    }

    /**
     * Forces all children of this share to inherit the permissions set on this
     * share.
     *
     * @return TODO
     */
    function inheritPermissions()
    {
        $c_list = $this->datatree->get(DATATREE_FORMAT_FLAT, $this->getName(), true);
        if (is_a($c_list, 'PEAR_Error') || !$c_list) {
            // If we got back an error or an empty array, just return it.
            return $c_list;
        }
        unset($c_list[$this->getName()]);

        $children = $this->_shareOb->getShares(array_keys($c_list));
        if (is_a($children, 'PEAR_Error')) {
            return $children;
        }

        $perm = $this->getPermission();
        foreach ($children as $child) {
            $child->setPermission($perm);
        }

        return true;
    }

    /**
     * Sets one of the attributes of the object.
     *
     * @param string   The attribute to set.
     * @param mixed    The value for $attribute.
     * @param boolean  Determines whether the backend should be updated or not.
     */
    function set($attribute, $value, $update = false)
    {
        parent::set($attribute, $value);
        if ($update) {
            $this->save();
        }
    }

    /**
     * Adds a bookmark to this folder.
     *
     * @param array $properties The initial properties for the new
     *                          bookmark. Expected values are
     *                          'bookmark_url', 'bookmark_title', and
     *                          'bookmark_description'.
     *
     * @return  The id of the new bookmark.
     */
    function addBookmark($properties)
    {
        $properties['folder_id'] = $this->getId();
        $bookmark = new Trean_Bookmark($properties);
        return $bookmark->save();
    }

    /**
     * Adds a child folder to this folder.
     *
     * @param array $properties  The initial properties for the new folder.
     *                           Expected value is 'name'.
     *
     * @return  The id of the new folder.
     */
    function addFolder($properties)
    {
        $folder = $this->_shareOb->newFolder($this->getName() . ':' . strval(new Horde_Support_Uuid()), $properties);
        $this->_shareOb->addFolder($folder);
        return $this->datatree->getId($folder);
    }

    /**
     * Returns the id of this folder's parent folder.
     */
    function getParent()
    {
        $parent = $this->datatree->getParent($this->getName());
        return ($parent == DATATREE_ROOT) ? null : $parent;
    }

    /**
     * Maps this object's attributes from the data array into a format that we
     * can store in the attributes storage backend.
     *
     * @access protected
     *
     * @param boolean $permsonly  Only process permissions? Lets subclasses
     *                            override part of this method while handling
     *                            their additional attributes seperately.
     *
     * @return array  The attributes array.
     */
    function _toAttributes($permsonly = false)
    {
        // Default to no attributes.
        $attributes = array();

        foreach ($this->data as $key => $value) {
            if ($key == 'perm') {
                foreach ($value as $type => $perms) {
                    if (is_array($perms)) {
                        foreach ($perms as $member => $perm) {
                            $attributes[] = array('name' => 'perm_' . $type,
                                                  'key' => $member,
                                                  'value' => $perm);
                        }
                    } else {
                        $attributes[] = array('name' => 'perm_' . $type,
                                              'key' => '',
                                              'value' => $perms);
                    }
                }
            } elseif (!$permsonly) {
                $attributes[] = array('name' => $key,
                                      'key' => '',
                                      'value' => $value);
            }
        }

        return $attributes;
    }

    /**
     * Takes in a list of attributes from the backend and maps it to our
     * internal data array.
     *
     * @access protected
     *
     * @param array $attributes   The list of attributes from the backend
     *                            (attribute name, key, and value).
     * @param boolean $permsonly  Only process permissions? Lets subclasses
     *                            override part of this method while handling
     *                            their additional attributes seperately.
     */
    function _fromAttributes($attributes, $permsonly = false)
    {
        // Initialize data array.
        $this->data['perm'] = array();

        foreach ($attributes as $attr) {
            if (substr($attr['name'], 0, 4) == 'perm') {
                if (!empty($attr['key'])) {
                    $this->data['perm'][substr($attr['name'], 5)][$attr['key']] = $attr['value'];
                } else {
                    $this->data['perm'][substr($attr['name'], 5)] = $attr['value'];
                }
            } elseif (!$permsonly) {
                $this->data[$attr['name']] = $attr['value'];
            }
        }
    }
}
