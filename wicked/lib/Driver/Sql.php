<?php
/**
 * @package Wicked
 */

/**
 * Wicked storage implementation for the Horde_Db database abstraction layer.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Wicked
 */
class Wicked_Driver_Sql extends Wicked_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * A cached list of all available page names.
     *
     * @var array
     */
    protected $_pageNames;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        if (!isset($params['db'])) {
            throw new InvalidArgumentException('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge(array(
            'table' => 'wicked_pages',
            'historytable' => 'wicked_history',
            'attachmenttable' => 'wicked_attachments',
            'attachmenthistorytable' => 'wicked_attachment_history'
        ), $params);
        parent::__construct($params);
    }

    /**
     * Retrieves the page of a particular name from the database.
     *
     * @param string $pagename The name of the page to retrieve.
     *
     * @return array
     * @throws Wicked_Exception
     */
    public function retrieveByName($pagename)
    {
        $pages = $this->_retrieve(
            $this->_params['table'],
            array('page_name = ?', array($this->_convertToDriver($pagename))));

        if (!empty($pages[0])) {
            return $pages[0];
        }

        throw new Wicked_Exception($pagename . ' not found');
    }

    /**
     * Retrieves a historic version of a page.
     *
     * @param string $pagename  The name of the page to retrieve.
     * @param string $version   The version to retrieve.
     *
     * @return array  The page hash.
     * @throws Wicked_Exception
     */
    public function retrieveHistory($pagename, $version)
    {
        if (!preg_match('/^\d+$/', $version)) {
            throw new Wicked_Exception('invalid version number');
        }

        return $this->_retrieve(
            $this->_params['historytable'],
            array('page_name = ? AND page_version = ?',
                  array($this->_convertToDriver($pagename), (int)$version)));
    }

    public function getPageById($id)
    {
        return $this->_retrieve($this->_params['table'],
                                array('page_id = ?', array((int)$id)));
    }

    public function getAllPages()
    {
        return $this->_retrieve($this->_params['table'], '', 'page_name');
    }

    public function getHistory($pagename)
    {
        return $this->_retrieve(
            $this->_params['historytable'],
            array('page_name = ?', array($this->_convertToDriver($pagename))),
            'page_version DESC');
    }

    /**
     * Returns the most recently changed pages.
     *
     * @param integer $days  The number of days to look back.
     *
     * @return array  Pages.
     * @throws Wicked_Exception
     */
    public function getRecentChanges($days = 3)
    {
        $where = array('version_created > ?', array(time() - (86400 * $days)));
        $result = $this->_retrieve($this->_params['table'],
                                   $where,
                                   'version_created DESC');
        $result2 = $this->_retrieve($this->_params['historytable'],
                                    $where,
                                    'version_created DESC');
        return array_merge($result, $result2);
    }

    /**
     * Returns the most popular pages.
     *
     * @param integer $limit  The number of most popular pages to return.
     *
     * @return array  Pages.
     * @throws Wicked_Exception
     */
    public function mostPopular($limit = 10)
    {
        return $this->_retrieve($this->_params['table'], '',
                                'page_hits DESC', $limit);
    }

    /**
     * Returns the least popular pages.
     *
     * @param integer $limit  The number of least popular pages to return.
     *
     * @return array  Pages.
     * @throws Wicked_Exception
     */
    public function leastPopular($limit = 10)
    {
        return $this->_retrieve($this->_params['table'], '',
                                'page_hits ASC', $limit);
    }

    public function searchTitles($searchtext)
    {
        $searchtext = $this->_convertToDriver($searchtext);
        try {
            $where = $this->_db->buildClause('page_name', 'LIKE', $searchtext);
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }
        return $this->_retrieve($this->_params['table'], $where);
    }

    /**
     * Finds pages with matches in text or title.
     *
     * @param string $searchtext  The search expression (Google-like).
     * @param boolean $title      Search both page title and text?
     *
     * @return array  A list of pages.
     * @throws Wicked_Exception
     */
    public function searchText($searchtext, $title = true)
    {
        $searchtext = $this->_convertToDriver($searchtext);

        try {
            $textClause = Horde_Db_SearchParser::parse('page_text', $searchtext);
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }

        if ($title) {
            try {
                $nameClause = Horde_Db_SearchParser::parse('page_name', $searchtext);
            } catch (Horde_Db_Exception $e) {
                throw new Wicked_Exception($e);
            }

            $where = '(' . $nameClause . ') OR (' . $textClause . ')';
        } else {
            $where = $textClause;
        }

        return $this->_retrieve($this->_params['table'], $where);
    }

    public function getBackLinks($pagename)
    {
        try {
            $where = $this->_db->buildClause(
                'page_text', 'LIKE', $this->_convertToDriver($pagename));
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }
        $pages = $this->_retrieve($this->_params['table'], $where);

        /* We've cast a wide net, so now we filter out pages which don't
         * actually refer to $pagename. */
        /* @todo this should match the current wiki engine's syntax. */
        $patterns = array('/\(\(' . preg_quote($pagename, '/') . '(?:\|[^)]+)?\)\)/');
        if (preg_match('/^' . Wicked::REGEXP_WIKIWORD . '$/', $pagename)) {
            $patterns[] = '/\b' . preg_quote($pagename, '/') . '\b/';
        }

        foreach ($pages as $key => $page) {
            $match = false;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $page['page_text'])) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                unset($pages[$key]);
            }
        }

        return $pages;
    }

    public function getMatchingPages($searchtext,
                                     $matchType = Wicked_Page::MATCH_ANY)
    {
        $searchtext = strtolower($searchtext);

        try {
            /* Short circuit the simple case. */
            if ($matchType == Wicked_Page::MATCH_ANY) {
                return $this->_retrieve(
                    $this->_params['table'],
                    'LOWER(page_name) LIKE ' . $this->_db->quote('%' . $searchtext . '%'));
            }

            $clauses = array();
            if ($matchType & Wicked_Page::MATCH_LEFT) {
                $clauses[] = 'LOWER(page_name) LIKE ' . $this->_db->quote($searchtext . '%');
            }
            if ($matchType & Wicked_Page::MATCH_RIGHT) {
                $clauses[] = 'LOWER(page_name) LIKE ' . $this->_db->quote('%' . $searchtext);
            }
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }

        if (!$clauses) {
            return array();
        }

        return $this->_retrieve($this->_params['table'],
                                implode(' OR ', $clauses));
    }

    public function getLikePages($pagename)
    {
        if (Horde_String::isUpper($pagename, 'UTF-8')) {
            $firstword = $pagename;
            $lastword = null;
        } else {
            /* Get the first and last word of the page name. */
            $count = preg_match_all('/[A-Z][a-z]*/', $pagename, $matches);
            if (!$count) {
                return array();
            }
            $matches = $matches[0];

            $firstword = $matches[0];
            $lastword = $matches[$count - 1];

            if (strlen($firstword) == 1 && strlen($matches[1]) == 1) {
                for ($i = 1; $i < $count; $i++) {
                    $firstword .= $matches[$i];
                    if (isset($matches[$i + 1]) && strlen($matches[$i + 1]) > 1) {
                        break;
                    }
                }
            }

            if (strlen($lastword) == 1 && strlen($matches[$count - 2]) == 1) {
                for ($i = $count - 2; $i > 0; $i--) {
                    $lastword = $matches[$i] . $lastword;
                    if (isset($matches[$i - 1]) && strlen($matches[$i - 1]) > 1) {
                        break;
                    }
                }
            }
        }

        try {
            $where = $this->_db->buildClause('page_name', 'LIKE', $firstword);
            if (!empty($lastword) && $lastword != $firstword) {
                $where .= ' OR ' . $this->_db->buildClause('page_name', 'LIKE', $lastword);
            }
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }

        return $this->_retrieve($this->_params['table'], $where);
    }

    /**
     * Retrieves data on files attached to a page.
     *
     * @param string $pageId        This is the Id of the page for which we'd
     *                              like to find attached files.
     * @param boolean $allversions  Whether to include all versions. If false
     *                              or omitted, only the most recent version
     *                              of each attachment is returned.
     * @return array  An array of key/value arrays describing the attached
     *                files.
     * @throws Wicked_Exception
     */
    public function getAttachedFiles($pageId, $allversions = false)
    {
        $where = array('page_id = ?', array((int)$pageId));
        $data = $this->_retrieve($this->_params['attachmenttable'], $where);

        if ($allversions) {
            $more_data = $this->_retrieve(
                $this->_params['attachmenthistorytable'], $where);
            $data = array_merge($data, $more_data);
        }

        foreach (array_keys($data) as $key) {
            $data[$key]['attachment_name'] = $this->_convertFromDriver($data[$key]['attachment_name']);
        }
        usort($data, array($this, '_getAttachedFiles_usort'));

        return $data;
    }

    protected function _getAttachedFiles_usort($a, $b)
    {
        if ($res = strcmp($a['attachment_name'], $b['attachment_name'])) {
            return $res;
        }
        return ($a['attachment_version'] - $b['attachment_version']);
    }

    /**
     * Removes a single version or all versions of an attachment from
     * $pageId.
     *
     * @param integer $pageId     The Id of the page the file is attached to.
     * @param string $attachment  The name of the file.
     * @param string $version     If specified, the version to delete. If null,
     *                            then all versions of $attachment will be
     *                            removed.
     *
     * @throws Wicked_Exception
     */
    public function removeAttachment($pageId, $attachment, $version = null)
    {
        /* Try to delete from the VFS first. */
        parent::removeAttachment($pageId, $attachment, $version);

        /* First try against the current attachments table. */
        $sql = 'DELETE FROM ' . $this->_params['attachmenttable'] .
            ' WHERE page_id = ? AND attachment_name = ?';
        $params = array((int)$pageId, $attachment);
        if (!is_null($version)) {
            $sql .= ' AND attachment_version = ?';
            $params[] = (int)$version;
        }

        try {
            $this->_db->beginDbTransaction();
            $result = $this->_db->delete($sql, $params);

            /* Now try against the attachment history table. $params is
             * unchanged. */
            $sql = 'DELETE FROM ' . $this->_params['attachmenthistorytable'] .
                ' WHERE page_id = ? AND attachment_name = ?';
            if (!is_null($version)) {
                $sql .= ' AND attachment_version = ?';
            }
            $this->_db->delete($sql, $params);
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Wicked_Exception($e);
        }
    }

    /**
     * Removes all attachments from a page.
     *
     * @param integer $pageId  A page ID.
     *
     * @throws Wicked_Exception
     */
    public function removeAllAttachments($pageId)
    {
        /* Try to delete from the VFS first. */
        $result = parent::removeAllAttachments($pageId);

        $params = array((int)$pageId);
        try {
            $this->_db->beginDbTransaction();
            /* First try against the current attachments table. */
            $result = $this->_db->delete(
                'DELETE FROM ' . $this->_params['attachmenttable']
                . ' WHERE page_id = ?',
                $params);

            /* Now try against the attachment history table. $params is
             * unchanged. */
            $this->_db->delete(
                'DELETE FROM ' . $this->_params['attachmenthistorytable']
                . ' WHERE page_id = ?',
                $params);
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Wicked_Exception($e);
        }
    }

    /**
     * Handles the driver-specific portion of attaching a file.
     *
     * Wicked_Driver::attachFile() calls down to this method for the driver-
     * specific portion, and then uses VFS to store the attachment.
     *
     * @param array $file  See Wicked_Driver::attachFile().
     *
     * @return integer  The new version of the file attached.
     * @throws Wicked_Exception
     */
    protected function _attachFile($file)
    {
        if ($file['change_author'] === false) {
            $file['change_author'] = null;
        }

        $attachments = $this->_retrieve(
            $this->_params['attachmenttable'],
            array('page_id = ? AND attachment_name = ?',
                  array((int)$file['page_id'], $file['attachment_name'])));

        if ($attachments) {
            $version = $attachments[0]['attachment_version'] + 1;

            try {
                $this->_db->beginDbTransaction();
                $this->_db->insert(
                    sprintf('INSERT INTO %s (page_id, attachment_name, attachment_version, attachment_created, change_author, change_log) SELECT page_id, attachment_name, attachment_version, attachment_created, change_author, change_log FROM %s WHERE page_id = ? AND attachment_name = ?',
                            $this->_params['attachmenthistorytable'],
                            $this->_params['attachmenttable']),
                    array((int)$file['page_id'],
                          $file['attachment_name']));

                $this->_db->update(
                    sprintf('UPDATE %s SET attachment_version = ?, change_log = ?, change_author = ?, attachment_created = ? WHERE page_id = ? AND attachment_name = ?',
                            $this->_params['attachmenttable']),
                    array((int)$version,
                          $this->_convertToDriver($file['change_log']),
                          $this->_convertToDriver($file['change_author']),
                          time(),
                          (int)$file['page_id'],
                          $this->_convertToDriver($file['attachment_name'])));
                $this->_db->commitDbTransaction();
            } catch (Horde_Db_Exception $e) {
                $this->_db->rollbackDbTransaction();
                throw new Wicked_Exception($e);
            }
        } else {
            $version = 1;
            try {
                $this->_db->insert(
                    sprintf('INSERT INTO %s (page_id, attachment_version, change_log, change_author, attachment_created, attachment_name) VALUES (?, 1, ?, ?, ?, ?)',
                            $this->_params['attachmenttable']),
                    array((int)$file['page_id'],
                          $this->_convertToDriver($file['change_log']),
                          $this->_convertToDriver($file['change_author']),
                          time(),
                          $this->_convertToDriver($file['attachment_name'])));
            } catch (Horde_Db_Exception $e) {
                throw new Wicked_Exception($e);
            }
        }

        return $version;
    }

    /**
     * Logs a page view.
     *
     * @param string $pagename  The page that was viewed.
     *
     * @throws Wicked_Exception
     */
    public function logPageView($pagename)
    {
        try {
            return $this->_db->update(
                'UPDATE ' . $this->_params['table']
                . ' SET page_hits = page_hits + 1 WHERE page_name = ?',
                array($this->_convertToDriver($pagename)));
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }
    }

    /**
     * Logs an attachment download.
     *
     * @param integer $pageid     The page with the attachment.
     * @param string $attachment  The attachment name.
     *
     * @throws Wicked_Exception
     */
    public function logAttachmentDownload($pageid, $attachment)
    {
        try {
            return $this->_db->update(
                'UPDATE ' . $this->_params['attachmenttable']
                . ' SET attachment_hits = attachment_hits + 1'
                . ' WHERE page_id = ? AND attachment_name = ?',
                array((int)$pageid, $this->_convertToDriver($attachment)));
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }
    }

    /**
     * Creates a new page.
     *
     * @param string $pagename  The new page's name.
     * @param string $text      The new page's text.
     *
     * @throws Wicked_Exception
     */
    public function newPage($pagename, $text)
    {
        if (!strlen($pagename)) {
            throw new Wicked_Exception(_("Page name must not be empty"));
        }

        if ($GLOBALS['browser']->isRobot()) {
            throw new Wicked_Exception(_("Robots are not allowed to create pages"));
        }

        $author = $GLOBALS['registry']->getAuth();
        if ($author === false) {
            $author = null;
        }

        /* Attempt the insertion/update query. */
        try {
            $page_id = $this->_db->insert(
                'INSERT INTO ' . $this->_params['table']
                . ' (page_name, page_text, version_created, page_version,'
                . ' page_hits, change_author) VALUES (?, ?, ?, 1, 0, ?)',
                array($this->_convertToDriver($pagename),
                      $this->_convertToDriver($text),
                      time(),
                      $author));
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }

        /* Send notification. */
        $url = Wicked::url($pagename, true, -1);
        Wicked::mail("Created page: $url\n\n$text\n",
                     array('Subject' => '[' . $GLOBALS['registry']->get('name')
                           . '] created: ' . $pagename));

        /* Call getPages with no caching so that the new list of pages is
         * read in. */
        $this->getPages(true, true);

        return $page_id;
    }

    /**
     * Renames a page, keeping the page's history.
     *
     * @param string $pagename  The name of the page to rename.
     * @param string $newname   The page's new name.
     *
     * @throws Wicked_Exception
     */
    public function renamePage($pagename, $newname)
    {
        try {
            $this->_db->beginDbTransaction();
            $this->_db->update(
                'UPDATE ' . $this->_params['table']
                . ' SET page_name = ? WHERE page_name = ?',
                array($this->_convertToDriver($newname),
                      $this->_convertToDriver($pagename)));

            $this->_db->update(
                'UPDATE ' . $this->_params['historytable']
                . ' SET page_name = ? WHERE page_name = ?',
                array($this->_convertToDriver($newname),
                      $this->_convertToDriver($pagename)));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Wicked_Exception($e);
        }

        $changelog = sprintf(_("Renamed page from %s"), $pagename);
        $newPage = $this->retrieveByName($newname);

        /* Call getPages with no caching so that the new list of pages is
         * read in. */
        $this->getPages(true, true);

        return $this->updateText($newname, $newPage['page_text'], $changelog);
    }

    public function updateText($pagename, $text, $changelog)
    {
        if (!$this->pageExists($pagename)) {
            return $this->newPage($pagename, $text);
        }

        /* Copy the old version into the page history. */
        Horde::logMessage('Page ' . $pagename . ' saved with user agent ' . $GLOBALS['browser']->getAgentString(), 'DEBUG');

        $author = $GLOBALS['registry']->getAuth();
        if ($author === false) {
            $author = null;
        }

        try {
            $this->_db->beginDbTransaction();
            $this->_db->insert(
                sprintf('INSERT INTO %s (page_id, page_name, page_text, page_version, version_created, change_author, change_log) SELECT page_id, page_name, page_text, page_version, version_created, change_author, change_log FROM %s WHERE page_name = ?',
                        $this->_params['historytable'],
                        $this->_params['table']),
                array($this->_convertToDriver($pagename)));

            /* Now move on to updating the record. */
            $this->_db->update(
                'UPDATE ' . $this->_params['table']
                . ' SET change_author = ?, page_text = ?, change_log = ?,'
                . ' version_created = ?, page_version = page_version + 1'
                . ' WHERE page_name = ?',
                array($author,
                      $this->_convertToDriver($text),
                      $this->_convertToDriver($changelog),
                      time(),
                      $this->_convertToDriver($pagename)));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Wicked_Exception($e);
        }
    }

    public function getPages($special = true, $no_cache = false)
    {
        if (!isset($this->_pageNames) || $no_cache) {
            try {
                $result = $this->_db->selectAssoc(
                    'SELECT page_id, page_name FROM ' . $this->_params['table']);
            } catch (Horde_Db_Exception $e) {
                throw new Wicked_Exception($e);
            }
            $this->_pageNames = $this->_convertFromDriver($result);
        }

        if ($special) {
            $this->_pageNames += $this->getSpecialPages();
        }

        return $this->_pageNames;
    }

    /**
     */
    public function removeVersion($pagename, $version)
    {
        $values = array($this->_convertToDriver($pagename), (int)$version);

        /* We need to know if we're deleting the current version. */
        try {
            $result = $this->_db->selectValue(
                'SELECT 1 FROM ' . $this->_params['table']
                . ' WHERE page_name = ? AND page_version = ?',
                $values);
        } catch (Horde_Db_Exception $e) {
            $result = false;
        }

        if (!$result) {
            /* Removing a historical revision - we can just slice it out of the
             * history table. $values is unchanged. */
            try {
                $this->_db->delete(
                    'DELETE FROM ' . $this->_params['historytable']
                    . ' WHERE page_name = ? and page_version = ?',
                    $values);
            } catch (Horde_Db_Exception $e) {
                throw new Wicked_Exception($e);
            }
            return;
        }

        /* We're deleting the current version. Have to promote the next-most
         * revision from the history table. */
        try {
            $query = 'SELECT * FROM ' . $this->_params['historytable'] .
                ' WHERE page_name = ? ORDER BY page_version DESC';
            $query = $this->_db->addLimitOffset($query, array('limit' => 1));
            $revision = $this->_db->selectOne(
                $query, array($this->_convertToDriver($pagename)));

            /* Replace the current version of the page with the version being
             * promoted. */
            $this->_db->beginDbTransaction();
            $this->_db->update(
                'UPDATE ' . $this->_params['table'] . ' SET' .
                ' page_text = ?, page_version = ?,' .
                ' version_created = ?, change_author = ?, change_log = ?' .
                ' WHERE page_name = ?',
                array($revision['page_text'],
                      (int)$revision['page_version'],
                      (int)$revision['version_created'],
                      $revision['change_author'],
                      $revision['change_log'],
                      $this->_convertToDriver($pagename)));

            /* Finally, remove the version that we promoted from the history
             * table. */
            $this->_db->delete(
                'DELETE FROM ' . $this->_params['historytable'] .
                ' WHERE page_name = ? and page_version = ?',
                array($this->_convertToDriver($pagename),
                      (int)$revision['page_version']));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Wicked_Exception($e);
        }
    }

    /**
     */
    public function removeAllVersions($pagename)
    {
        $this->_pageNames = null;

        try {
            $this->_db->beginDbTransaction();
            $this->_db->delete(
                'DELETE FROM ' . $this->_params['table']
                . ' WHERE page_name = ?',
                array($this->_convertToDriver($pagename)));

            $this->_db->delete(
                'DELETE FROM ' . $this->_params['historytable']
                . ' WHERE page_name = ?',
                array($this->_convertToDriver($pagename)));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Wicked_Exception($e);
        }

        /* Remove attachments and do other cleanup. */
        return parent::removeAllVersions($pagename);
    }

    /**
     * Retrieves a set of pages matching an SQL WHERE clause.
     *
     * @param string $table        Table to retrieve pages from.
     * @param array|string $where  Where clause for sql statement (without the
     *                             'WHERE'). If an array the 1st element is the
     *                             clause with placeholder, the 2nd element the
     *                             values.
     * @param string $orderBy      Order results by this column.
     * @param integer $limit       Maximum number of pages to fetch.
     *
     * @return array  A list of page hashes.
     * @throws Wicked_Exception
     */
    protected function _retrieve($table, $where, $orderBy = null, $limit = null)
    {
        $query = 'SELECT * FROM ' . $table;
        $values = array();
        if (!empty($where)) { 
            $query .= ' WHERE ';
            if (is_array($where)) {
                $query .= $where[0];
                $values = $where[1];
            } else {
                $query .= $where;
            }
        }
        if (!empty($orderBy)) {
            $query .= ' ORDER BY ' . $orderBy;
        }
        if (!empty($limit)) {
            try {
                $query = $this->_db->addLimitOffset($query, array('limit' => $limit));
            } catch (Horde_Db_Exception $e) {
                throw new Wicked_Exception($e);
            }
        }

        try {
            $result = $this->_db->select($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Wicked_Exception($e);
        }

        $pages = array();
        foreach ($result as $row) {
            if (isset($row['page_name'])) {
                $row['page_name'] = $this->_convertFromDriver($row['page_name']);
            }
            if (isset($row['page_text'])) {
                $row['page_text'] = $this->_convertFromDriver($row['page_text']);
            }
            if (isset($row['change_log'])) {
                $row['change_log'] = $this->_convertFromDriver($row['change_log']);
            }
            $pages[] = $row;
        }

        return $pages;
    }

    /**
     * Returns the charset used by the backend.
     *
     * @return string  The backend's charset
     */
    public function getCharset()
    {
        return $this->_db->getOption('charset');
    }

    /**
     * Converts a value from the driver's charset to the default charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    protected function _convertFromDriver($value)
    {
        return Horde_String::convertCharset($value, $this->getCharset(), 'UTF-8');
    }

    /**
     * Converts a value from the default charset to the driver's charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    protected function _convertToDriver($value)
    {
        return Horde_String::convertCharset($value, 'UTF-8', $this->getCharset());
    }
}
