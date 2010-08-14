<?php
/**
 * $Horde: trean/lib/Bookmarks.php,v 1.104 2009-12-10 19:24:09 mrubinsk Exp $
 *
 * @package Trean
 */

/** DataTree */
require_once 'Horde/DataTree.php';

/**
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Ben Chavet <ben@horde.org>
 * @package Trean
 */
class Trean_Bookmarks {

    /**
     * Pointer to a DataTree instance to manage/store shares
     *
     * @var DataTree
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
    function Trean_Bookmarks()
    {
        global $conf, $registry;

        if (empty($conf['datatree']['driver'])) {
            throw new Horde_Exception('You must configure a DataTree backend to use Trean.');
        }

        $driver = $conf['datatree']['driver'];
        $this->_datatree = &DataTree::singleton(
            $driver,
            array_merge(Horde::getDriverConfig('datatree', $driver), array('group' => 'horde.shares.trean'))
        );

        try {
            Horde::callHook('share_init', array($this, 'trean'));
        } catch (Horde_Exception_HookNotSet $e) {}
    }

    /**
     * Search all folders that the user has permissions to.
     */
    function searchBookmarks($search_criteria, $search_operator = 'OR',
                             $sortby = 'title', $sortdir = 0, $from = 0, $count = 0)
    {
        // Validate the search operator (AND or OR).
        switch ($search_operator) {
        case 'AND':
        case 'OR':
            break;

        default:
            $search_operator = 'AND';
        }

        // Get the folder ids to search.
        $folderIds = $this->listFolders($GLOBALS['registry']->getAuth(), Horde_Perms::READ);

        $clauses = array();
        $values = array();
        foreach ($search_criteria as $criterion) {
            $clause = Horde_SQL::buildClause($GLOBALS['trean_db'],
                                             'bookmark_' . $criterion[0],
                                             $criterion[1],
                                             Horde_String::convertCharset($criterion[2],
                                                                    $GLOBALS['conf']['sql']['charset']),
                                             true,
                                             isset($criterion[3]) ? $criterion[3] : array());
            $clauses[] = $clause[0];
            $values = array_merge($values, $clause[1]);
        }

        $GLOBALS['trean_db']->setLimit($count, $from);

        $sql = 'SELECT bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description,
                       bookmark_clicks, bookmark_rating
                FROM trean_bookmarks
                WHERE folder_id IN (' . implode(',', $folderIds) . ')
                      AND (' . implode(' ' . $search_operator . ' ', $clauses) . ')
                ORDER BY bookmark_' . $sortby . ($sortdir ? ' DESC' : '');
        $query = $GLOBALS['trean_db']->prepare($sql);
        if (is_a($query, 'PEAR_Error')) {
            Horde::logMessage($query, __FILE__, __LINE__, PEAR_LOG_ERR);
            return array();
        }

        $result = $query->execute($values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return array();
        }

        return Trean_Bookmarks::resultSet($result->fetchAll(MDB2_FETCHMODE_ASSOC));
    }

    /**
     * Sort bookmarks from all folders the user can access by a
     * specific criteria.
     */
    function sortBookmarks($sortby = 'title', $sortdir = 0, $from = 0, $count = 10)
    {
        // List the folders to search.
        $folderIds = $this->listFolders($GLOBALS['registry']->getAuth(), Horde_Perms::READ);

        // Make sure $sortby is a valid field.
        switch ($sortby) {
        case 'rating':
        case 'clicks':
            break;

        default:
            $sortby = 'title';
        }

        if ($count > 100) {
            return PEAR::raiseError('Max of 100 results');
        }

        $GLOBALS['trean_db']->setLimit($count, $from);
        return Trean_Bookmarks::resultSet($GLOBALS['trean_db']->queryAll('
            SELECT bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description,
                   bookmark_clicks, bookmark_rating
            FROM trean_bookmarks
            WHERE folder_id IN (' . implode(',', $folderIds) . ')
            ORDER BY bookmark_' . $sortby . ($sortdir ? ' DESC' : ''), null, MDB2_FETCHMODE_ASSOC));
    }

    /**
     * Returns the number of bookmarks in all folders.
     *
     * @return integer  The number of all bookmarks.
     */
    function countBookmarks()
    {
        $folderIds = $this->listFolders($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
        $sql = 'SELECT COUNT(*) FROM trean_bookmarks WHERE folder_id IN (' . implode(',', $folderIds) . ')';
        return $GLOBALS['trean_db']->queryOne($sql);
    }

    /**
     * Return counts on grouping bookmarks by a specific property.
     */
    function groupBookmarks($groupby)
    {
        $folderIds = $this->listFolders($GLOBALS['registry']->getAuth(), Horde_Perms::READ);

        switch ($groupby) {
        case 'status':
            $sql = 'SELECT bookmark_http_status AS status, COUNT(*) AS count
                    FROM trean_bookmarks
                    WHERE folder_id IN (' . implode(',', $folderIds) . ')
                    GROUP BY bookmark_http_status';
            break;

        default:
            return array();
        }

        return $GLOBALS['trean_db']->queryAll($sql, null, MDB2_FETCHMODE_ASSOC);
    }

    /**
     * Returns an array of DataTreeObject_Folder objects corresponding to the
     * given set of unique IDs, with the details retrieved appropriately.
     *
     * @param array $cids  The array of ids to retrieve.
     *
     * @return array  The requested shares.
     */
    function &getShares($cids)
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
            $shares = $this->_datatree->getObjects($missing_ids, 'DataTreeObject_Folder');
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
     * Checks if a share exists in the system.
     *
     * @param string $share  The share to check.
     *
     * @return boolean  True if the share exists, false otherwise.
     */
    function exists($share)
    {
        if (isset($this->_cache[$share])) {
            return true;
        }

        return $this->_datatree->exists($share);
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
    function &getFolders($userid, $perm = Horde_Perms::SHOW, $parent = null, $allLevels = true)
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

        try {
            return Horde::callHook('share_list', array($userid, $perm, null, $shares));
        } catch (Horde_Exception_HookNotSet $e) {
            return $shares;
        }
    }

    /**
     * Returns a new folder object.
     *
     * @param string $name       The folder's internal name.
     * @param array $properties  The folder's initial properties. If set, a
     *                           'name' value is expected.
     *
     * @return DataTreeObject_Folder  A new folder object.
     */
    function &newFolder($name, $properties = null)
    {
        if (empty($name)) {
            $error = PEAR::raiseError(_("Folder names must be non-empty"));
            return $error;
        }

        $folder = new DataTreeObject_Folder($name);
        $folder->setDataTree($this->_datatree);
        $folder->setShareOb($this);
        $folder->set('owner', $GLOBALS['registry']->getAuth());
        $folder->set('name', isset($properties['name']) ? $properties['name'] : '');
        return $folder;
    }

    /**
     * Returns a DataTreeObject_Folder object corresponding to the given unique
     * ID, with the details retrieved appropriately.
     *
     * @param string $cid  The id of the folder to retrieve.
     *
     * @return DataTreeObject_Folder The requested folder.
     */
    function &getFolder($cid)
    {
        if (isset($this->_shareMap[$cid])) {
            $share = $this->_cache[$this->_shareMap[$cid]];
        } else {
            $share = $this->_datatree->getObjectById($cid, 'DataTreeObject_Folder');
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
     * Returns the bookmark corresponding to the given id.
     *
     * @param integer $id  The ID of the bookmark to retrieve.
     *
     * @return Trean_Bookmark  The bookmark object corresponding to the given name.
     */
    function getBookmark($id)
    {
        $bookmark = $GLOBALS['trean_db']->queryRow('
            SELECT bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description,
                   bookmark_clicks, bookmark_rating
            FROM trean_bookmarks
            WHERE bookmark_id = ' . (int)$id, null, MDB2_FETCHMODE_ASSOC);
        if (is_null($bookmark)) {
            return PEAR::raiseError('not found');
        } elseif (is_a($bookmark, 'PEAR_Error')) {
            return $bookmark;
        } else {
            $bookmark = $this->resultSet(array($bookmark));
            return array_pop($bookmark);
        }
    }

    /**
     * Stores a new folder permanently.
     *
     * @param DataTreeObject_Folder $folder  The folder to add.
     */
    function addFolder($folder)
    {
        if (!is_a($folder, 'DataTreeObject_Folder')) {
            return PEAR::raiseError('Folders must be DataTreeObject_Folder objects or extend that class.');
        }

        /* Give the owner full access */
        $perm = $GLOBALS['injector']->getInstance('Horde_Perms')->newPermission($folder->getName());
        $perm->addUserPermission($folder->get('owner'), Horde_Perms::SHOW, false);
        $perm->addUserPermission($folder->get('owner'), Horde_Perms::READ, false);
        $perm->addUserPermission($folder->get('owner'), Horde_Perms::EDIT, false);
        $perm->addUserPermission($folder->get('owner'), Horde_Perms::DELETE, false);

        $folder->setPermission($perm, false);

        try {
            $result = Horde::callHook('share_add', array($folder));
        } catch (Horde_Exception_HookNotSet $e) {}

        $result = $this->_datatree->add($folder);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Store new share in the caches. */
        $id = $folder->getId();
        $name = $folder->getName();
        $this->_cache[$name] = $folder;
        $this->_shareMap[$id] = $name;

        /* Reset caches that depend on unknown criteria. */
        $this->_listCache = array();
        $this->_counts = array();

        return $result;
    }

    /**
     * Removes a folder.
     *
     * @param DataTreeObject_Folder $folder  The folder to
     *                                       remove.
     * @param boolean $force                 Force the removal of
     *                                       every child?
     */
    function removeFolder($folder, $force = false)
    {
        if (!is_a($folder, 'DataTreeObject_Folder')) {
            return PEAR::raiseError('Folders must be DataTreeObject_Folder objects or extend that class.');
        }

        try {
            $result = Horde::callHook('share_remove', array($folder));
        } catch (Horde_Exception_HookNotSet $e) {}

        return $this->_datatree->remove($folder, $force);
    }

    /**
     * Removes a Trean_Bookmark from the backend.
     *
     * @param Trean_Bookmark $bookmark  The bookmark to remove.
     */
    function removeBookmark($bookmark)
    {
        /* Make sure $bookmark is a Trean_Bookmark; if not, try
         * loading it. */
        if (!is_a($bookmark, 'Trean_Bookmark')) {
            $b = $this->getBookmark($bookmark);
            if (is_a($b, 'PEAR_Error')) {
                return $b;
            }
            $bookmark = $b;
        }

        /* Check permissions. */
        $folder = $this->getFolder($bookmark->folder);
        if (!$folder->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
            return PEAR::raiseError('permission denied');
        }

        /* TODO: Decrement favicon refcount. */

        /* Delete from SQL. */
        $GLOBALS['trean_db']->exec('DELETE FROM trean_bookmarks WHERE bookmark_id = ' . (int)$bookmark->id);

        return true;
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
     * Move $folder to be a child of $new_parent.
     */
    function move($folder, $new_parent)
    {
        if (!is_a($folder, 'DataTreeObject_Folder')) {
            return PEAR::raiseError('Folders must be DataTreeObject_Folder objects or extend that class.');
        }
        if (!is_a($new_parent, 'DataTreeObject_Folder')) {
            return PEAR::raiseError('Folders must be DataTreeObject_Folder objects or extend that class.');
        }
        return $this->_datatree->move($folder, $new_parent);
    }

    /**
     * Create Trean_Bookmark objects for each row in a SQL result.
     * @static
     */
    function resultSet($bookmarks)
    {
        if (is_null($bookmarks)) {
            return array();
        } elseif (is_a($bookmarks, 'PEAR_Error')) {
            return $bookmarks;
        }

        $objects = array();
        foreach ($bookmarks as $bookmark) {
            foreach ($bookmark as $key => $value)
            if (!empty($value) && !is_numeric($value)) {
                $cvBookmarks[$key] = Horde_String::convertCharset(
                    $value, $GLOBALS['conf']['sql']['charset']);
            } else {
                $cvBookmarks[$key] = $value;
            }
            $objects[] = new Trean_Bookmark($cvBookmarks);
        }
        return $objects;
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
            $group = Horde_Group::singleton();
            $groups = $group->getGroupMemberships($userid, true);
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
 * Extension of the DataTreeObject class for storing bookmark folders.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Trean
 */
class DataTreeObject_Folder extends DataTreeObject {

    /**
     * The Trean_Bookmarks object which this share came from - needed
     * for updating data in the backend to make changes stick, etc.
     *
     * @var Trean_Bookmarks
     */
    var $_shareOb;

    /**
     * The DataTreeObject_Folder constructor. Just makes sure to call the parent
     * constructor so that the share's name is set properly.
     *
     * @param string $id  The id of the share.
     */
    function DataTreeObject_Folder($id)
    {
        parent::DataTreeObject($id);
        if (is_null($this->data)) {
            $this->data = array();
        }
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['datatree'], $properties['_shareOb']);
        $properties = array_keys($properties);
        return $properties;
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
     * Lists the bookmarks in this folder.
     *
     * @param integer $from   The bookmark to start fetching.
     * @param integer $count  The numer of bookmarks to return.
     */
    function listBookmarks($sortby = 'title', $sortdir = 0, $from = 0, $count = 0)
    {
        // Make sure $sortby is a valid field.
        switch ($sortby) {
        case 'rating':
        case 'clicks':
            break;

        default:
            $sortby = 'title';
        }

        $GLOBALS['trean_db']->setLimit($count, $from);
        return Trean_Bookmarks::resultSet($GLOBALS['trean_db']->queryAll('
            SELECT bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description,
                   bookmark_clicks, bookmark_rating
            FROM trean_bookmarks
            WHERE folder_id = ' . (int)$this->getId() . '
            ORDER BY bookmark_' . $sortby . ($sortdir ? ' DESC' : ''), null, MDB2_FETCHMODE_ASSOC));
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

/**
 * @author  Ben Chavet <ben@horde.org>
 * @package Trean
 */
class Trean_Bookmark {

    var $id = null;
    var $url = null;
    var $title = '';
    var $description = '';
    var $clicks = 0;
    var $rating = 0;
    var $http_status = null;
    var $folder;
    var $favicon;

    function Trean_Bookmark($bookmark = array())
    {
        if ($bookmark) {
            $this->url = $bookmark['bookmark_url'];
            $this->title = $bookmark['bookmark_title'];
            $this->description = $bookmark['bookmark_description'];
            $this->folder = $bookmark['folder_id'];

            if (!empty($bookmark['bookmark_id'])) {
                $this->id = (int)$bookmark['bookmark_id'];
            }
            if (!empty($bookmark['bookmark_clicks'])) {
                $this->clicks = (int)$bookmark['bookmark_clicks'];
            }
            if (!empty($bookmark['bookmark_rating'])) {
                $this->rating = (int)$bookmark['bookmark_rating'];
            }
            if (!empty($bookmark['bookmark_http_status'])) {
                $this->http_status = $bookmark['bookmark_http_status'];
            }
        }
    }

    /**
     * Copy this bookmark into $folder.
     */
    function copyTo($folder)
    {
        if (!is_a($folder, 'DataTreeObject_Folder')) {
            return PEAR::raiseError('Folders must be DataTreeObject_Folder objects or extend that class.');
        }

        return $folder->addBookmark(array('bookmark_url' => $this->url,
                                          'bookmark_title' => $this->title,
                                          'bookmark_description' => $this->description));
    }

    /**
     * Save bookmark.
     */
    function save()
    {
        if ($this->id) {
            // Update an existing bookmark.
            $update = $GLOBALS['trean_db']->prepare('
                UPDATE trean_bookmarks
                SET folder_id = ?,
                    bookmark_url = ?,
                    bookmark_title = ?,
                    bookmark_description = ?,
                    bookmark_clicks = ?,
                    bookmark_rating = ?
                WHERE bookmark_id = ?',
                array('integer', 'text', 'text', 'text', 'integer', 'integer', 'integer')
            );
            if (is_a($update, 'PEAR_Error')) {
                return $update;
            }
            $result = $update->execute(array($this->folder,
                                             Horde_String::convertCharset($this->url, $GLOBALS['registry']->getCharset(), $GLOBALS['conf']['sql']['charset']),
                                             Horde_String::convertCharset($this->title, $GLOBALS['registry']->getCharset(), $GLOBALS['conf']['sql']['charset']),
                                             Horde_String::convertCharset($this->description, $GLOBALS['registry']->getCharset(), $GLOBALS['conf']['sql']['charset']),
                                             $this->clicks,
                                             $this->rating,
                                             $this->id));
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
            return $result;
        }

        if (!$this->folder || !strlen($this->url)) {
            return PEAR::raiseError('Incomplete bookmark');
        }

        // Saving a new bookmark.
        $bookmark_id = $GLOBALS['trean_db']->nextId('trean_bookmarks');
        if (is_a($bookmark_id, 'PEAR_Error')) {
            Horde::logMessage($bookmark_id, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $bookmark_id;
        }

        $insert = $GLOBALS['trean_db']->prepare('
            INSERT INTO trean_bookmarks
                (bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description,
                 bookmark_clicks, bookmark_rating)
            VALUES (?, ?, ?, ?, ?, ?, ?)',
            array('integer', 'integer', 'text', 'text', 'text', 'integer', 'integer')
        );
        if (is_a($insert, 'PEAR_Error')) {
            return $insert;
        }

        $result = $insert->execute(array($bookmark_id,
                                         $this->folder,
                                         Horde_String::convertCharset($this->url, $GLOBALS['registry']->getCharset(), $GLOBALS['conf']['sql']['charset']),
                                         Horde_String::convertCharset($this->title, $GLOBALS['registry']->getCharset(), $GLOBALS['conf']['sql']['charset']),
                                         Horde_String::convertCharset($this->description, $GLOBALS['registry']->getCharset(), $GLOBALS['conf']['sql']['charset']),
                                         $this->clicks,
                                         $this->rating,
        ));
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        $this->id = (int)$bookmark_id;
        return $this->id;
    }

}
