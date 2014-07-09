<?php
/**
 * Copyright 2004-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsdl.php
 * @package  Trean
 */
/**
 * Trean_Bookmarks:: Handles basic management of bookmark storage.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsdl.php
 * @package  Trean
 */
class Trean_Bookmarks
{
    /**
     * @var Content_Users_Manager
     */
    protected $_userManager;

    /**
     * @var integer
     */
    protected $_userId;

    /**
     * Constructor.
     *
     * @param Content_Users_Manager  A user manager object.
     */
    public function __construct(Content_Users_Manager $userManager)
    {
        $this->_userManager = $userManager;
        try {
            $this->_userId = current($this->_userManager->ensureUsers($GLOBALS['registry']->getAuth()));
        } catch (Content_Exception $e) {
            throw new Trean_Exception($e);
        }
    }

    /**
     * Create a new bookmark for the current user.
     *
     * @param array $properties  The bookmark property array.
     * @param boolean $crawl     If true (default) attempt to crawl the URL.
     *
     * @return Trean_Bookmark
     */
    public function newBookmark(array $properties, $crawl = true)
    {
        $properties['user_id'] = $this->_userId;
        if (empty($properties['bookmark_dt'])) {
            $properties['bookmark_dt'] = new Horde_Date(time());
        }
        $bookmark = new Trean_Bookmark($properties);
        $bookmark->save($crawl);
        return $bookmark;
    }

    /**
     * List bookmarks, sorted and paged as specified.
     *
     * @param string $sortyby   Field to sort by.
     * @param integer $sortdir  Direction of sort.
     * @param integer $from     Starting bookmark.
     * @param integer $to       Ending bookmark.
     *
     * @return array  An array of Trean_Bookmark objects.
     */
    public function listBookmarks($sortby = 'title', $sortdir = 0, $from = 0, $count = 0)
    {
        $values = array($this->_userId);

        $sql = 'SELECT bookmark_id, user_id, bookmark_url, bookmark_title, bookmark_description, bookmark_clicks, bookmark_http_status, favicon_url, bookmark_dt
                FROM trean_bookmarks
                WHERE user_id = ?
                ORDER BY bookmark_' . $sortby . ($sortdir ? ' DESC' : '');
        $sql = $GLOBALS['trean_db']->addLimitOffset($sql, array('limit' => $count, 'offset' => $from));

        return $this->_resultSet($GLOBALS['trean_db']->selectAll($sql, $values));
    }

    /**
     * Search bookmarks.
     *
     * @param string $q  The search text.
     *
     * @return array An array of Trean_Bookmark objects that match the search.
     * @throws Trean_Exception
     */
    public function searchBookmarks($q)
    {
        $indexer = $GLOBALS['injector']->getInstance('Content_Indexer');
        try {
            $search = $indexer->search('horde-user-' . $this->_userId, 'trean-bookmark', $q);
        } catch (Content_Exception $e) {
            throw new Trean_Exception($e);
        }
        if (!$search->hits->total) {
            return array();
        }
        $bookmarkIds = array();
        foreach ($search->hits->hits as $bookmarkHit) {
            $bookmarkIds[] = (int)$bookmarkHit->_id;
        }

        $sql = 'SELECT bookmark_id, user_id, bookmark_url, bookmark_title, bookmark_description, bookmark_clicks, bookmark_http_status, favicon_url, bookmark_dt
                FROM trean_bookmarks
                WHERE user_id = ? AND bookmark_id IN (' . implode(',', $bookmarkIds) . ')';
        $values = array($this->_userId);

        return $this->_resultSet($GLOBALS['trean_db']->selectAll($sql, $values));
    }

    /**
     * Returns the number of bookmarks.
     *
     * @return integer  The number of all bookmarks.
     * @throws Trean_Exception
     */
    public function countBookmarks()
    {
        $sql = 'SELECT COUNT(*) FROM trean_bookmarks WHERE user_id = ?';
        try {
            return $GLOBALS['trean_db']->selectValue($sql, array($this->_userId));
        } catch (Horde_Db_Exception $e) {
            throw new Trean_Exception($e);
        }
    }

    /**
     * Return counts on grouping bookmarks by a specific property.
     *
     * @param string $groupby  The field to group on. (i.e., 'status').
     *
     * @return array A hash of results.
     * @throws Trean_Exception
     */
    public function groupBookmarks($groupby)
    {
        switch ($groupby) {
        case 'status':
            $sql = 'SELECT bookmark_http_status AS status, COUNT(*) AS count
                    FROM trean_bookmarks
                    GROUP BY bookmark_http_status';
            break;

        default:
            return array();
        }

        try {
            return $GLOBALS['trean_db']->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Trean_Exception($e);
        }
    }

    /**
     * Returns the bookmark corresponding to the given id.
     *
     * @param integer $id  The ID of the bookmark to retrieve.
     *
     * @return Trean_Bookmark  The bookmark object corresponding to the given name.
     * @throws Horde_Exception_NotFound, Trean_Exception
     */
    public function getBookmark($id)
    {
        try {
            $bookmark = $GLOBALS['trean_db']->selectOne('
                SELECT bookmark_id, user_id, bookmark_url, bookmark_title, bookmark_description, bookmark_clicks, bookmark_http_status, favicon_url, bookmark_dt
                FROM trean_bookmarks
                WHERE bookmark_id = ' . (int)$id);
        } catch (Horde_Db_Exception $e) {
            throw new Trean_Exception($e);
        }
        if (is_null($bookmark)) {
            throw new Horde_Exception_NotFound();
        }

        $bookmark = $this->_resultSet(array($bookmark));
        return array_pop($bookmark);
    }

    /**
     * Removes a Trean_Bookmark from the backend.
     *
     * @param Trean_Bookmark $bookmark  The bookmark to remove.
     * @throws Horde_Exception_PermissionDenied, Trean_Exception
     */
    public function removeBookmark(Trean_Bookmark $bookmark)
    {
        /* Check permissions. */
        if ($bookmark->userId != $this->_userId) {
            throw new Horde_Exception_PermissionDenied();
        }

        /* Untag */
        $tagger = $GLOBALS['injector']->getInstance('Trean_Tagger');
        $tagger->replaceTags((string)$bookmark->id, array(), $GLOBALS['registry']->getAuth(), 'bookmark');

        /* @TODO delete from content index? */
        //$indexer->index('horde-user-' . $this->_userId, 'trean-bookmark', $this->_bookmarkId, json_encode(array(

        /* Delete from SQL. */
        try {
            $GLOBALS['trean_db']->delete('DELETE FROM trean_bookmarks WHERE bookmark_id = ' . (int)$bookmark->id);
        } catch (Horde_Db_Exception $e) {
            throw new Trean_Exception($e);
        }

        return true;
    }

    /**
     * Creates Trean_Bookmark objects for each row in a SQL result.
     *
     * @param array $bookmarks  An array of query results.
     *
     * @return array  An array of Trean_Bookmark objects.
     */
    protected function _resultSet($bookmarks)
    {
        if (is_null($bookmarks)) {
            return array();
        }

        $objects = array();
        $tagger = $GLOBALS['injector']->getInstance('Trean_Tagger');
        $charset = $GLOBALS['trean_db']->getOption('charset');
        foreach ($bookmarks as $bookmark) {
            foreach ($bookmark as $key => $value) {
                if (!empty($value) && !is_numeric($value)) {
                    $cvBookmarks[$key] = Horde_String::convertCharset($value, $charset, 'UTF-8');
                } else {
                    $cvBookmarks[$key] = $value;
                }
            }
            $cvBookmarks['bookmark_tags'] = $tagger->getTags((string)$cvBookmarks['bookmark_id'], 'bookmark');
            $objects[] = new Trean_Bookmark($cvBookmarks);
        }

        return $objects;
    }

}
