<?php
/**
 * @package Wicked
 */

/**
 * Wicked storage implementation for PHP's PEAR database abstraction
 * layer.
 *
 * The table structure can be created by the scripts/drivers/wicked_foo.sql
 * script.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Wicked
 */
class Wicked_Driver_sql extends Wicked_Driver {

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    var $_db;

    /**
     * Constructs a new Wicked SQL driver object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Wicked_Driver_sql($params = array())
    {
        parent::Wicked_Driver($params);
    }

    /**
     * Retrieves the page of a particular name from the database.
     *
     * @param string $pagename The name of the page to retrieve.
     *
     * @return array
     * @throws Wicked_Exception
     */
    function retrieveByName($pagename)
    {
        $where = 'page_name = ' . $this->_db->quoteString($this->_convertToDriver($pagename));

        $pages = $this->_retrieve($this->_params['table'], $where);

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
    function retrieveHistory($pagename, $version)
    {
        if (empty($version) or !preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            throw new Wicked_Exception('invalid version number');
        }

        list($major, $minor) = explode('.', $version);
        $where = sprintf('page_name = %s AND page_majorversion = %s AND ' .
                         'page_minorversion = %s',
                         $this->_db->quoteString($this->_convertToDriver($pagename)),
                         (int)$major, (int)$minor);

        return $this->_retrieve($this->_params['historytable'], $where);
    }

    function getPage($pagename)
    {
        $where = 'page_name = ' . $this->_db->quoteString($this->_convertToDriver($pagename));
        return $this->_retrieve($this->_params['table'], $where);
    }

    function getPageById($id)
    {
        $where = 'page_id = ' . (int)$id;
        return $this->_retrieve($this->_params['table'], $where);
    }

    function getAllPages()
    {
        return $this->_retrieve($this->_params['table'], '', 'page_name');
    }

    function getHistory($pagename)
    {
        $where = 'page_name = ' . $this->_db->quoteString($this->_convertToDriver($pagename)) .
                 ' ORDER BY page_majorversion DESC, page_minorversion DESC';

        return $this->_retrieve($this->_params['historytable'], $where);
    }

    /**
     * Returns the most recently changed pages.
     *
     * @param integer $days  The number of days to look back.
     *
     * @return array  Pages.
     * @throws Wicked_Exception
     */
    function getRecentChanges($days = 3)
    {
        $where = 'version_created > ' . (time() - (86400 * $days));
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
    function mostPopular($limit = 10)
    {
        return $this->_retrieve($this->_params['table'], '', 'page_hits DESC', $limit);
    }

    /**
     * Returns the least popular pages.
     *
     * @param integer $limit  The number of least popular pages to return.
     *
     * @return array  Pages.
     * @throws Wicked_Exception
     */
    function leastPopular($limit = 10)
    {
        return $this->_retrieve($this->_params['table'], '', 'page_hits ASC', $limit);
    }

    function searchTitles($searchtext)
    {
        require_once 'Horde/SQL.php';
        $searchtext = $this->_convertToDriver($searchtext);
        $where = Horde_SQL::buildClause($this->_db, 'page_name', 'LIKE', $searchtext);
        return $this->_retrieve($this->_params['table'], $where);
    }

    /**
     * Finds pages with matches in text or title.
     *
     * @param string  $searchtext  The search expression (Google-like).
     * @param boolean $title default true  If true, both page title and text
     *                                     are searched.  If false, only page
     *                                     text is searched.
     *
     * @return array  A list of pages.
     * @throws Wicked_Exception
     */
    function searchText($searchtext, $title = true)
    {
        require_once 'Horde/SQL/Keywords.php';
        $searchtext = $this->_convertToDriver($searchtext);

        $textClause = Horde_SQL_Keywords::parse('page_text', $searchtext);
        if (is_a($textClause, 'PEAR_Error')) {
            throw new Wicked_Exception($textClause);
        }

        if ($title) {
            $nameClause = Horde_SQL_Keywords::parse('page_name', $searchtext);
            if (is_a($nameClause, 'PEAR_Error')) {
                throw new Wicked_Exception($nameClause);
            }

            $where = '(' . $nameClause . ') OR (' . $textClause . ')';
        } else {
            $where = $textClause;
        }

        return $this->_retrieve($this->_params['table'], $where);
    }

    function getBackLinks($pagename)
    {
        $where = 'page_text LIKE ' . $this->_db->quoteString('%' . $this->_convertToDriver($pagename) . '%');
        $pages = $this->_retrieve($this->_params['table'], $where);

        /* We've cast a wide net, so now we filter out pages which don't
         * actually refer to $pagename. */
        $patterns = array('/\(\(' . preg_quote($pagename, '/') . '(?:\|[^)]+)?\)\)/');
        if (preg_match('/^' . Wicked::REGEXP_WIKIWORD . '$/', $pagename)) {
            $patterns[] = '/\b' . preg_quote($pagename, '/') . '\b/';
        }

        foreach ($pages as $key => $page) {
            $match = false;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $page['page_text'])) {
                    $match = true;
                }
            }
            if (!$match) {
                unset($pages[$key]);
            }
        }

        return $pages;
    }

    function getMatchingPages($searchtext, $matchType = WICKED_PAGE_MATCH_ANY)
    {
        $searchtext = Horde_String::lower($searchtext);

        /* Short circuit the simple case. */
        if ($matchType == WICKED_PAGE_MATCH_ANY) {
            return $this->_retrieve($this->_params['table'],
                                    'LOWER(page_name) LIKE ' . $this->_db->quoteString('%' . $searchtext . '%'));
        }

        $clauses = array();
        if ($matchType & WICKED_PAGE_MATCH_LEFT) {
            $clauses[] = 'LOWER(page_name) LIKE ' . $this->_db->quoteString($searchtext . '%');
        }
        if ($matchType & WICKED_PAGE_MATCH_RIGHT) {
            $clauses[] = 'LOWER(page_name) LIKE ' . $this->_db->quoteString('%' . $searchtext);
        }

        if (!$clauses) {
            return array();
        }

        return $this->_retrieve($this->_params['table'], implode(' OR ', $clauses));
    }

    function getLikePages($pagename)
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

        require_once 'Horde/SQL.php';

        $where = Horde_SQL::buildClause($this->_db, 'page_name', 'LIKE', $firstword);
        if (!empty($lastword) && $lastword != $firstword) {
            $where .= ' OR ' . Horde_SQL::buildClause($this->_db, 'page_name', 'LIKE', $lastword);
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
    function getAttachedFiles($pageId, $allversions = false)
    {
        $where = 'page_id = ' . (int)$pageId;
        $data = $this->_retrieve($this->_params['attachmenttable'], $where);

        if ($allversions) {
            $more_data = $this->_retrieve($this->_params['attachmenthistorytable'], $where);
            $data = array_merge($data, $more_data);
        }

        foreach (array_keys($data) as $key) {
            $data[$key]['attachment_name'] = $this->_convertFromDriver($data[$key]['attachment_name']);
        }

        usort($data, array($this, '_getAttachedFiles_usort'));
        return $data;
    }

    function _getAttachedFiles_usort($a, $b)
    {
        $res = strcmp($a['attachment_name'], $b['attachment_name']);
        if ($res != 0) {
            return $res;
        }
        $res = ($a['attachment_majorversion'] - $b['attachment_minorversion']);
        if ($res != 0) {
            return $res;
        }

        return ($a['attachment_minorversion'] - $b['attachment_minorversion']);
    }

    /**
     * Remove a single version or all versions of an attachment from
     * $pageId. Calls parent::removeAttachment() to delete files from
     * VFS.
     *
     * @param integer $pageId  The Id of the page the file is attached to.
     * @param string $attachment  The name of the file.
     * @param string $version  If specified, the version to delete. If null,
     *                         then all versions of $attachment will be removed.
     *
     * @throws Wicked_Exception
     */
    function removeAttachment($pageId, $attachment, $version = null)
    {
        /* Try to delete from the VFS first. */
        parent::removeAttachment($pageId, $attachment, $version);

        /* First try against the current attachments table. */
        $sql = 'DELETE FROM ' . $this->_params['attachmenttable'] .
            ' WHERE page_id = ? AND attachment_name = ?';
        $params = array($pageId, $attachment);
        if (!is_null($version)) {
            list($major, $minor) = explode('.', $version);
            $sql .= ' AND attachment_majorversion = ? AND attachment_minorversion = ?';
            $params[] = (int)$major;
            $params[] = (int)$minor;
        }

        Horde::logMessage('Wicked_Driver_sql::removeAttachment: ' . $sql, 'DEBUG');

        $result = $this->_db->delete($sql, $params);

        /* Now try against the attachment history table. $params is
         * unchanged. */
        $sql = 'DELETE FROM ' . $this->_params['attachmenthistorytable'] .
            ' WHERE page_id = ? AND attachment_name = ?';
        if (!is_null($version)) {
            $sql .= ' AND attachment_majorversion = ? AND attachment_minorversion = ?';
        }

        Horde::logMessage('Wicked_Driver_sql::removeAttachment: ' . $sql, 'DEBUG');

        $this->_db->delete($sql, $params);
    }

    /**
     * Removes all attachments from $pageId. Calls
     * parent::removeAllAttachments() to delete files from VFS.
     *
     * @param integer $pageId  The Id of the page to remove attachments from.
     *
     * @throws Wicked_Exception
     */
    function removeAllAttachments($pageId)
    {
        /* Try to delete from the VFS first. */
        $result = parent::removeAllAttachments($pageId);

        /* First try against the current attachments table. */
        $sql = 'DELETE FROM ' . $this->_params['attachmenttable'] .
            ' WHERE page_id = ?';
        $params = array($pageId);

        Horde::logMessage('Wicked_Driver_sql::removeAllAttachments: ' . $sql, 'DEBUG');

        $result = $this->_db->delete($sql, $params);

        /* Now try against the attachment history table. $params is
         * unchanged. */
        $sql = 'DELETE FROM ' . $this->_params['attachmenthistorytable'] .
            ' WHERE page_id = ?';

        Horde::logMessage('Wicked_Driver_sql::removeAllAttachments: ' . $sql, 'DEBUG');

        $this->_db->delete($sql, $params);
    }

    /**
     * Handles the driver-specific portion of attaching a file.
     *
     * Wicked_Driver::attachFile() calls down to this method for the driver-
     * specific portion, and then uses VFS to store the attachment.
     *
     * @access protected
     *
     * @param array $file  See Wicked_Driver::attachFile().
     *
     * @return string  The new version of the file attached.
     * @throws Wicked_Exception
     */
    function _attachFile($file)
    {
        $where = 'page_id = ' . intval($file['page_id']) .
                 ' AND attachment_name = ' . $this->_db->quoteString($file['attachment_name']);
        $attachments = $this->_retrieve($this->_params['attachmenttable'], $where);

        if ($file['change_author'] === false) {
            $file['change_author'] = null;
        }

        if ($attachments) {
            list($old) = $attachments;
            $majorversion = $old['attachment_majorversion'];
            $minorversion = $old['attachment_minorversion'];
            if ($file['minor']) {
                $minorversion++;
            } else {
                $majorversion++;
                $minorversion = 0;
            }

            $sql = sprintf('INSERT INTO %s (page_id, attachment_name, attachment_majorversion, attachment_minorversion, attachment_created, change_author, change_log) SELECT page_id, attachment_name, attachment_majorversion, attachment_minorversion, attachment_created, change_author, change_log FROM %s WHERE page_id = %s AND attachment_name = %s',
                           $this->_params['attachmenthistorytable'],
                           $this->_params['attachmenttable'],
                           intval($file['page_id']),
                           $this->_db->quoteString($file['attachment_name']));
            $this->_db->insert($sql);

            $sql = sprintf('UPDATE %s SET attachment_majorversion = %s, attachment_minorversion = %s, change_log = %s, change_author = %s, attachment_created = %s WHERE page_id = %d AND attachment_name = %s',
                           $this->_params['attachmenttable'],
                           intval($majorversion),
                           intval($minorversion),
                           $this->_db->quoteString($this->_convertToDriver($file['change_log'])),
                           $this->_db->quoteString($this->_convertToDriver($file['change_author'])),
                           intval(time()),
                           intval($file['page_id']),
                           $this->_db->quoteString($this->_convertToDriver($file['attachment_name'])));
            $this->_db->update($sql);
        } else {
            $majorversion = 1;
            $minorversion = 0;
            $sql = sprintf('INSERT INTO %s (page_id, attachment_majorversion, attachment_minorversion, change_log, change_author, attachment_created, attachment_name) VALUES (%d, 1, 0, %s, %s, %s, %s)',
                           $this->_params['attachmenttable'],
                           intval($file['page_id']),
                           $this->_db->quoteString($this->_convertToDriver($file['change_log'])),
                           $this->_db->quoteString($this->_convertToDriver($file['change_author'])),
                           intval(time()),
                           $this->_db->quoteString($this->_convertToDriver($file['attachment_name'])));
            $this->_db->insert($sql);
        }

        return (int)$majorversion . '.' . (int)$minorversion;
    }

    /**
     * Log a hit to $pagename.
     *
     * @param string $pagename  The page that was viewed.
     *
     * @throws Wicked_Exception
     */
    function logPageView($pagename)
    {
        $query = 'UPDATE ' . $this->_params['table'] .
                 ' SET page_hits = page_hits + 1 WHERE page_name = ?';
        $values = array($this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::logPageView(' . $pagename . '): ' . $query, 'DEBUG');

        return $this->_db->update($query, $values);
    }

    /**
     * Creates a new page.
     *
     * @param string $pagename  The new page's name.
     * @param string $text      The new page's text.
     *
     * @throws Wicked_Exception
     */
    function newPage($pagename, $text)
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

        $query = 'INSERT INTO ' . $this->_params['table'] . ' ' .
                 '(page_name, page_text, ' .
                 'version_created, page_majorversion, ' .
                 'page_minorversion, page_hits, change_author) ' .
                 'VALUES (?, ?, ?, 1, 0, 0, ?)';
        $values = array(
            $this->_convertToDriver($pagename),
            $this->_convertToDriver($text),
            time(),
            $author,
        );

        Horde::logMessage('Wicked_Driver_sql::newPage(): ' . $query, 'DEBUG');

        /* Attempt the insertion/update query. */
        $page_id = $this->_db->insert($query, $values);

        /* Send notification. */
        $url = Wicked::url($pagename, true, -1);
        Wicked::mail("Created page: $url\n\n$text\n", array(
            'Subject' => '[' . $GLOBALS['registry']->get('name') .
                '] created: ' . $pagename));

        /* Call getPages with no caching so that the new list of pages is
         * read in. */
        $this->getPages(true, true);
        return $page_id;
    }

    /**
     * Rename a page (and keep the page's history).
     *
     * @param string $pagename  The name of the page to rename.
     * @param string $newname   The page's new name.
     *
     * @throws Wicked_Exception
     */
    function renamePage($pagename, $newname)
    {
        $query = 'UPDATE ' . $this->_params['table'] .
                 ' SET page_name = ? WHERE page_name = ?';
        $values = array($this->_convertToDriver($newname), $this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::renamePage(): ' . $query, 'DEBUG');

        $this->_db->update($query, $values);

        $query = 'UPDATE ' . $this->_params['historytable'] .
                 ' SET page_name = ? WHERE page_name = ?';
        $values = array($this->_convertToDriver($newname), $this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::renamePage(): ' . $query, 'DEBUG');

        $this->_db->update($query, $values);

        $changelog = sprintf(_("Renamed page from %s"), $pagename);
        $newPage = $this->retrieveByName($newname);

        /* Call getPages with no caching so that the new list of pages is
         * read in. */
        $this->getPages(true, true);
        return $this->updateText($newname, $newPage['page_text'], $changelog, true);
    }

    function updateText($pagename, $text, $changelog, $minorchange)
    {
        if (!$this->pageExists($pagename)) {
            return $this->newPage($pagename, $text);
        }

        /* Copy the old version into the page history. */
        $query = sprintf(
            'INSERT INTO %s (page_id, page_name, page_text, page_majorversion, page_minorversion, version_created, change_author, change_log)' .
            ' SELECT page_id, page_name, page_text, page_majorversion, page_minorversion, version_created, change_author, change_log FROM %s WHERE page_name = ?',
            $this->_params['historytable'],
            $this->_params['table']);
        $values = array($this->_convertToDriver($pagename));

        Horde::logMessage('Page ' . $pagename . ' saved with user agent ' . $GLOBALS['browser']->getAgentString(), 'DEBUG');
        Horde::logMessage('Wicked_Driver_sql::updateText(): ' . $query, 'DEBUG');

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Wicked_Exception($e);
        }

        /* Now move on to updating the record. */
        if ($minorchange) {
            $versionchange = 'page_minorversion = page_minorversion + 1';
        } else {
            $versionchange = 'page_majorversion = page_majorversion + 1, page_minorversion = 0';
        }

        $author = $GLOBALS['registry']->getAuth();
        if ($author === false) {
            $author = null;
        }

        $query = 'UPDATE ' . $this->_params['table'] .
                 ' SET change_author = ?, page_text = ?, change_log = ?, version_created = ?, ' . $versionchange .
                 ' WHERE page_name = ?';
        $values = array($author,
                        $this->_convertToDriver($text),
                        $this->_convertToDriver($changelog),
                        time(),
                        $this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::updateText(): ' . $query, 'DEBUG');

        $this->_db->update($query, $values);
    }

    function getPages($special = true, $no_cache = false)
    {
        static $pageNames;
        if (!isset($pageNames) || $no_cache) {
            $query = 'SELECT page_name FROM ' . $this->_params['table'];
            Horde::logMessage('Wicked_Driver_sql::getPages(): ' . $query, 'DEBUG');
            try {
                $result = $this->_db->selectValues($query);
            } catch (Horde_Db_Exception $e) {
                throw new Wicked_Exception($e);
            }
            $pageNames = $this->_convertFromDriver($result);
        }
        if ($special) {
            return $pageNames + $this->getSpecialPages();
        }

        return $pageNames;
    }

    /**
     */
    function removeVersion($pagename, $version)
    {
        list($major, $minor) = explode('.', $version);

        /* We need to know if we're deleting the current version. */
        $query = 'SELECT 1 FROM ' . $this->_params['table'] .
                 ' WHERE page_name = ? AND page_majorversion = ? AND page_minorversion = ?';
        $values = array($this->_convertToDriver($pagename), $major, $minor);

        Horde::logMessage('Wicked_Driver_sql::removeVersion(): ' . $query, 'DEBUG');

        try {
            $result = $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            $result = false;
        }

        if (!$result) {
            /* Removing a historical revision - we can just slice it out of the
             * history table. $values is unchanged. */
            $query = 'DELETE FROM ' . $this->_params['historytable'] .
                ' WHERE page_name = ? and page_majorversion = ? and page_minorversion = ?';
            Horde::logMessage('Wicked_Driver_sql::removeVersion(): ' . $query, 'DEBUG');
            $this->_db->delete($query, $values);
            return;
        }

        /* We're deleting the current version. Have to promote the
         * next-most revision from the history table. */
        $query = 'SELECT * FROM ' . $this->_params['historytable'] .
                 ' WHERE page_name = ? ORDER BY page_majorversion DESC, page_minorversion DESC';
        $query = $this->_db->addLimitOffset($query, array('limit' => 1));

        Horde::logMessage('Wicked_Driver_sql::removeVersion(): ' . $query, 'DEBUG');

        $revision = $this->_db->selectOne($query, array($this->_convertToDriver($pagename)), DB_FETCHMODE_ASSOC);

        /* Replace the current version of the page with the
         * version being promoted. */
        $query = 'UPDATE ' . $this->_params['table'] . ' SET' .
            ' page_text = ?, page_majorversion = ?, page_minorversion = ?,' .
            ' version_created = ?, change_author = ?, change_log = ?' .
            ' WHERE page_name = ?';
        $values = array($revision['page_text'],
                        $revision['page_majorversion'],
                        $revision['page_minorversion'],
                        $revision['version_created'],
                        $revision['change_author'],
                        $revision['change_log'],
                        $this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::removeVersion(): ' . $query, 'DEBUG');
        $this->_db->update($query, $values);

        /* Finally, remove the version that we promoted from the
         * history table. */
        $query = 'DELETE FROM ' . $this->_params['historytable'] .
            ' WHERE page_name = ? and page_majorversion = ? and page_minorversion = ?';
        $values = array($this->_convertToDriver($pagename), $revision['page_majorversion'], $revision['page_minorversion']);

        Horde::logMessage('Wicked_Driver_sql::removeVersion(): ' . $query, 'DEBUG');

        $this->_db->delete($query, $values);
    }

    /**
     */
    function removeAllVersions($pagename)
    {
        $this->_pageNames = null;

        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE page_name = ?';
        $values = array($this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::removeAllVersions(): ' . $query, 'DEBUG');

        $this->_db->delete($query, $values);

        $query = 'DELETE FROM ' . $this->_params['historytable'] .
                 ' WHERE page_name = ?';
        $values = array($this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::removeAllVersions(): ' . $query, 'DEBUG');

        $this->_db->delete($query, $values);

        /* Remove attachments and do other cleanup. */
        return parent::removeAllVersions($pagename);
    }

    /**
     * Retrieves a page or set of pages given an SQL WHERE clause.
     *
     * @access private
     *
     * @param string $table     Which table are we retrieving pages from?
     * @param string $sqlWhere  Where clause for sql statement (without the
     *                          'WHERE').
     * @param string $orderBy   What column should we order results by?
     * @param integer $limit    Maximum number of pages to fetch.
     *
     * @return array | object  Either an array of pages or PEAR::Error.
     */
    function _retrieve($table, $sqlWhere, $orderBy = null, $limit = null)
    {
        $query = sprintf('SELECT * FROM %s%s%s',
                         $table,
                         !empty($sqlWhere) ? ' WHERE ' . $sqlWhere : '',
                         !empty($orderBy) ? ' ORDER BY ' . $orderBy : '');
        if (!empty($limit)) {
            $query = $this->_db->addLimitOffset($query, array('limit' => $limit));
        }

        Horde::logMessage('Wicked_Driver_sql::_retrieve(): ' . $query, 'DEBUG');
        try {
            $result = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            Horde::logMessage($e);
            throw new Wicked_Exception($e);
        }

        $pages = array();
        $index = 0;
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
    function getCharset()
    {
        return $this->_params['charset'];
    }

    /**
     * Converts a value from the driver's charset to the default charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed        The converted value.
     */
    function _convertFromDriver($value)
    {
        return Horde_String::convertCharset($value, $this->getCharset(), 'UTF-8');
    }

    /**
     * Converts a value from the default charset to the driver's charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed        The converted value.
     */
    function _convertToDriver($value)
    {
        return Horde_String::convertCharset($value, 'UTF-8', $this->getCharset());
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @throws Wicked_Exception
     */
    function connect()
    {
        try {
            $this->_db = $GLOBALS['injector']->getInstance('Horde_Db_Adapter');
        } catch (Horde_Exception $e) {
            throw new Wicked_Exception($e);
        }

        $this->_params = array_merge(array(
            'table' => 'wicked_pages',
            'historytable' => 'wicked_history',
            'attachmenttable' => 'wicked_attachments',
            'attachmenthistorytable' => 'wicked_attachment_history'
        ), $this->_params);

        return true;
    }

}
