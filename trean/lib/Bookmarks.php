<?php
/**
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Ben Chavet <ben@horde.org>
 * @package Trean
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
     */
    public function __construct(Content_Users_Manager $userManager)
    {
        $this->_userManager = $userManager;
        $this->_userId = current($this->_userManager->ensureUsers($GLOBALS['registry']->getAuth()));
    }

    /**
     * Create a new bookmark for the current user
     *
     * @return Trean_Bookmark
     */
    public function newBookmark(array $properties)
    {
        $properties['user_id'] = $this->_userId;
        $properties['bookmark_dt'] = new Horde_Date(time());
        $bookmark = new Trean_Bookmark($properties);
        $bookmark->save();
        return $bookmark;
    }

    /**
     * Search bookmarks.
     */
    function listBookmarks($sortby = 'title', $sortdir = 0, $from = 0, $count = 0)
    {
        $values = array($this->_userId);

        $sql = 'SELECT bookmark_id, user_id, bookmark_url, bookmark_title, bookmark_description, bookmark_clicks, bookmark_http_status, bookmark_dt
                FROM trean_bookmarks
                WHERE user_id = ?
                ORDER BY bookmark_' . $sortby . ($sortdir ? ' DESC' : '');
        $sql = $GLOBALS['trean_db']->addLimitOffset($sql, array('limit' => $count, 'offset' => $from));

        return Trean_Bookmarks::resultSet($GLOBALS['trean_db']->selectAll($sql, $values));
    }

    /**
     * Search bookmarks.
     */
    function searchBookmarks($q)
    {
        $indexer = $GLOBALS['injector']->getInstance('Content_Indexer');
        $search = $indexer->search('horde-user-' . $this->_userId, 'trean-bookmark', $q);
        if (!$search->hits->total) {
            return array();
        }
        $bookmarkIds = array();
        foreach ($search->hits->hits as $bookmarkHit) {
            $bookmarkIds[] = (int)$bookmarkHit->_id;
        }

        $sql = 'SELECT bookmark_id, user_id, bookmark_url, bookmark_title, bookmark_description, bookmark_clicks, bookmark_http_status, bookmark_dt
                FROM trean_bookmarks
                WHERE user_id = ? AND bookmark_id IN (' . implode(',', $bookmarkIds) . ')';
        $values = array($this->_userId);

        return Trean_Bookmarks::resultSet($GLOBALS['trean_db']->selectAll($sql, $values));
    }

    /**
     * Returns the number of bookmarks.
     *
     * @return integer  The number of all bookmarks.
     */
    function countBookmarks()
    {
        $sql = 'SELECT COUNT(*) FROM trean_bookmarks WHERE user_id = ?';
        return $GLOBALS['trean_db']->selectValue($sql, array($this->_userId));
    }

    /**
     * Return counts on grouping bookmarks by a specific property.
     */
    function groupBookmarks($groupby)
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

        return $GLOBALS['trean_db']->selectAll($sql);
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
        $bookmark = $GLOBALS['trean_db']->selectOne('
            SELECT bookmark_id, user_id, bookmark_url, bookmark_title, bookmark_description, bookmark_clicks, bookmark_http_status, bookmark_dt
            FROM trean_bookmarks
            WHERE bookmark_id = ' . (int)$id);
        if (is_null($bookmark)) {
            throw new Trean_Exception('not found');
        }

        $bookmark = $this->resultSet(array($bookmark));
        return array_pop($bookmark);
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
            $bookmark = $this->getBookmark($bookmark);
        }

        /* Check permissions. */
        if ($bookmark->userId != $this->_userId) {
            throw new Trean_Exception('permission denied');
        }

        /* Untag */
        $tagger = $GLOBALS['injector']->getInstance('Trean_Tagger');
        $tagger->replaceTags((string)$bookmark->id, array(), $GLOBALS['registry']->getAuth(), 'bookmark');

        /* TODO: Decrement favicon refcount. */

        /* Delete from SQL. */
        $GLOBALS['trean_db']->delete('DELETE FROM trean_bookmarks WHERE bookmark_id = ' . (int)$bookmark->id);

        return true;
    }

    /**
     * Create Trean_Bookmark objects for each row in a SQL result.
     * @static
     */
    function resultSet($bookmarks)
    {
        if (is_null($bookmarks)) {
            return array();
        }

        $objects = array();
        $tagger = $GLOBALS['injector']->getInstance('Trean_Tagger');
        foreach ($bookmarks as $bookmark) {
            foreach ($bookmark as $key => $value) {
                if (!empty($value) && !is_numeric($value)) {
                    $cvBookmarks[$key] = Horde_String::convertCharset($value, $GLOBALS['conf']['sql']['charset'], 'UTF-8');
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
