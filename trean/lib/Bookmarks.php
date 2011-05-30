<?php
/**
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
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
     * Constructor.
     */
    function Trean_Bookmarks()
    {
        global $conf, $registry;

        if (empty($conf['datatree']['driver'])) {
            throw new Horde_Exception('You must configure a Horde_DataTree backend to use Trean.');
        }

        $driver = $conf['datatree']['driver'];
        $this->_datatree = Horde_DataTree::singleton(
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

        $clauses = array();
        $values = array();
        foreach ($search_criteria as $criterion) {
            $clause = $GLOBALS['trean_db']->buildClause(
                'bookmark_' . $criterion[0],
                $criterion[1],
                Horde_String::convertCharset($criterion[2],
                                             $GLOBALS['conf']['sql']['charset'],
                                             'UTF-8'),
                true,
                isset($criterion[3]) ? $criterion[3] : array());
            $clauses[] = $clause[0];
            $values = array_merge($values, $clause[1]);
        }

        $sql = 'SELECT bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description,
                       bookmark_clicks, bookmark_rating
                FROM trean_bookmarks
                WHERE user_uid = ?
                      AND (' . implode(' ' . $search_operator . ' ', $clauses) . ')
                ORDER BY bookmark_' . $sortby . ($sortdir ? ' DESC' : '');
        $sql = $GLOBALS['trean_db']->addLimitOffset($sql, array('limit' => $count, 'offset' => $from));

        return Trean_Bookmarks::resultSet($GLOBALS['trean_db']->selectAll($sql, array($GLOBALS['registry']->getAuth())));
    }

    /**
     * Sort bookmarks from all folders the user can access by a
     * specific criteria.
     */
    function sortBookmarks($sortby = 'title', $sortdir = 0, $from = 0, $count = 10)
    {
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

        $sql = '
            SELECT bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description,
                   bookmark_clicks, bookmark_rating
            FROM trean_bookmarks
            WHERE folder_id IN (' . implode(',', $folderIds) . ')
            ORDER BY bookmark_' . $sortby . ($sortdir ? ' DESC' : '');
        $sql = $GLOBALS['trean_db']->addLimitOffset($sql, array('limit' => $count, 'offset' => $from));
        return Trean_Bookmarks::resultSet($GLOBALS['trean_db']->selectAll($sql));
    }

    /**
     * Returns the number of bookmarks in all folders.
     *
     * @return integer  The number of all bookmarks.
     */
    function countBookmarks()
    {
        $sql = 'SELECT COUNT(*) FROM trean_bookmarks WHERE user_uid = ?';
        return $GLOBALS['trean_db']->selectValue($sql, array($GLOBALS['registry']->getAuth()));
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
                    WHERE folder_id IN (' . implode(',', $folderIds) . ')
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
            SELECT bookmark_id, folder_id, bookmark_url, bookmark_title, bookmark_description,
                   bookmark_clicks, bookmark_rating
            FROM trean_bookmarks
            WHERE bookmark_id = ' . (int)$id);
        if (is_null($bookmark)) {
            return PEAR::raiseError('not found');
        } else {
            $bookmark = $this->resultSet(array($bookmark));
            return array_pop($bookmark);
        }
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
        foreach ($bookmarks as $bookmark) {
            foreach ($bookmark as $key => $value) {
                if (!empty($value) && !is_numeric($value)) {
                    $cvBookmarks[$key] = Horde_String::convertCharset($value, $GLOBALS['conf']['sql']['charset'], 'UTF-8');
                } else {
                    $cvBookmarks[$key] = $value;
                }
            }
            $objects[] = new Trean_Bookmark($cvBookmarks);
        }
        return $objects;
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
     * Save bookmark.
     */
    function save()
    {
        if ($this->id) {
            // Update an existing bookmark.
            return $GLOBALS['trean_db']->update('
                UPDATE trean_bookmarks
                SET folder_id = ?,
                    bookmark_url = ?,
                    bookmark_title = ?,
                    bookmark_description = ?,
                    bookmark_clicks = ?,
                    bookmark_rating = ?
                WHERE bookmark_id = ?',
                array(
                    $this->folder,
                    Horde_String::convertCharset($this->url, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    Horde_String::convertCharset($this->title, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    Horde_String::convertCharset($this->description, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    $this->clicks,
                    $this->rating,
                    $this->id,
            ));
        }

        if (!$this->folder || !strlen($this->url)) {
            return PEAR::raiseError('Incomplete bookmark');
        }

        // Saving a new bookmark.
        $bookmark_id = $GLOBALS['trean_db']->insert('
            INSERT INTO trean_bookmarks
                (folder_id, bookmark_url, bookmark_title, bookmark_description,
                 bookmark_clicks, bookmark_rating)
            VALUES (?, ?, ?, ?, ?, ?)',
            array(
                $this->folder,
                Horde_String::convertCharset($this->url, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                Horde_String::convertCharset($this->title, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                Horde_String::convertCharset($this->description, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                $this->clicks,
                $this->rating,
        ));

        $this->id = (int)$bookmark_id;
        return $this->id;
    }
}
