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
     * @var DB
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
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function retrieveByName($pagename)
    {
        $where = 'page_name = ' . $this->_db->quote($this->_convertToDriver($pagename));

        $pages = $this->_retrieve($this->_params['table'], $where);
        if (is_a($pages, 'PEAR_Error')) {
            Horde::logMessage($pages, 'ERR');
            return $pages;
        }

        if (!empty($pages[0])) {
            return $pages[0];
        }

        return PEAR::raiseError($pagename . ' not found');
    }

    /**
     * Retrieves a historic version of a page.
     *
     * @param string $pagename  The name of the page to retrieve.
     * @param string $version   The version to retrieve.
     *
     * @return array  The page hash, or PEAR_Error on failure.
     */
    function retrieveHistory($pagename, $version)
    {
        if (empty($version) or !preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return PEAR::raiseError('invalid version number');
        }

        list($major, $minor) = explode('.', $version);
        $where = sprintf('page_name = %s AND page_majorversion = %s AND ' .
                         'page_minorversion = %s',
                         $this->_db->quote($this->_convertToDriver($pagename)),
                         (int)$major, (int)$minor);

        return $this->_retrieve($this->_params['historytable'], $where);
    }

    function getPage($pagename)
    {
        $where = 'page_name = ' . $this->_db->quote($this->_convertToDriver($pagename));
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
        $where = 'page_name = ' . $this->_db->quote($this->_convertToDriver($pagename)) .
                 ' ORDER BY page_majorversion DESC, page_minorversion DESC';

        return $this->_retrieve($this->_params['historytable'], $where);
    }

    /**
     * Returns the most recently changed pages.
     *
     * @param integer $days  The number of days to look back.
     *
     * @return mixed  An array of pages, or PEAR_Error on failure.
     */
    function getRecentChanges($days = 3)
    {
        $where = 'version_created > ' . (time() - (86400 * $days));

        $result = $this->_retrieve($this->_params['table'], $where, 'version_created DESC');
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $result2 = $this->_retrieve($this->_params['historytable'], $where, 'version_created DESC');
        if (is_a($result2, 'PEAR_Error')) {
            return $result2;
        }

        return array_merge($result, $result2);
    }

    /**
     * Returns the most popular pages.
     *
     * @param integer $limit  The number of most popular pages to return.
     *
     * @return mixed  An array of pages, or PEAR_Error on failure.
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
     * @return mixed  An array of pages, or PEAR_Error on failure.
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
     * @return array  A list of pages, or PEAR_Error on failure.
     */
    function searchText($searchtext, $title = true)
    {
        require_once 'Horde/SQL/Keywords.php';
        $searchtext = $this->_convertToDriver($searchtext);

        $textClause = Horde_SQL_Keywords::parse('page_text', $searchtext);
        if (is_a($textClause, 'PEAR_Error')) {
            return $textClause;
        }

        if ($title) {
            $nameClause = Horde_SQL_Keywords::parse('page_name', $searchtext);
            if (is_a($nameClause, 'PEAR_Error')) {
                return $nameClause;
            }

            $where = '(' . $nameClause . ') OR (' . $textClause . ')';
        } else {
            $where = $textClause;
        }

        return $this->_retrieve($this->_params['table'], $where);
    }

    function getBackLinks($pagename)
    {
        $where = 'page_text LIKE ' . $this->_db->quote('%' . $this->_convertToDriver($pagename) . '%');
        $pages = $this->_retrieve($this->_params['table'], $where);
        if (is_a($pages, 'PEAR_Error')) {
            return $pages;
        }

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
                                    'LOWER(page_name) LIKE ' . $this->_db->quote('%' . $searchtext . '%'));
        }

        $clauses = array();
        if ($matchType & WICKED_PAGE_MATCH_LEFT) {
            $clauses[] = 'LOWER(page_name) LIKE ' . $this->_db->quote($searchtext . '%');
        }
        if ($matchType & WICKED_PAGE_MATCH_RIGHT) {
            $clauses[] = 'LOWER(page_name) LIKE ' . $this->_db->quote('%' . $searchtext);
        }

        if (!$clauses) {
            return array();
        }

        return $this->_retrieve($this->_params['table'], implode(' OR ', $clauses));
    }

    function getLikePages($pagename)
    {
        if (Horde_String::isUpper($pagename)) {
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
     * @return mixed  An array of key/value arrays describing the attached
     *                files or a PEAR_Error:: instance on failure.
     */
    function getAttachedFiles($pageId, $allversions = false)
    {
        $where = 'page_id = ' . (int)$pageId;
        $data = $this->_retrieve($this->_params['attachmenttable'], $where);
        if (is_a($data, 'PEAR_Error')) {
            return $data;
        }

        if ($allversions) {
            $more_data = $this->_retrieve($this->_params['attachmenthistorytable'], $where);
            if (is_a($more_data, 'PEAR_Error')) {
                return $more_data;
            }
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
     * @return boolean|PEAR_Error  Either true or a PEAR_Error describing failure.
     */
    function removeAttachment($pageId, $attachment, $version = null)
    {
        /* Try to delete from the VFS first. */
        $result = parent::removeAttachment($pageId, $attachment, $version);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

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

        $result = $this->_db->query($sql, $params);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Now try against the attachment history table. $params is
         * unchanged. */
        $sql = 'DELETE FROM ' . $this->_params['attachmenthistorytable'] .
            ' WHERE page_id = ? AND attachment_name = ?';
        if (!is_null($version)) {
            $sql .= ' AND attachment_majorversion = ? AND attachment_minorversion = ?';
        }

        Horde::logMessage('Wicked_Driver_sql::removeAttachment: ' . $sql, 'DEBUG');

        return $this->_db->query($sql, $params);
    }

    /**
     * Removes all attachments from $pageId. Calls
     * parent::removeAllAttachments() to delete files from VFS.
     *
     * @param integer $pageId  The Id of the page to remove attachments from.
     *
     * @return boolean|PEAR_Error  Either true or a PEAR_Error describing failure.
     */
    function removeAllAttachments($pageId)
    {
        /* Try to delete from the VFS first. */
        $result = parent::removeAllAttachments($pageId);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* First try against the current attachments table. */
        $sql = 'DELETE FROM ' . $this->_params['attachmenttable'] .
            ' WHERE page_id = ?';
        $params = array($pageId);

        Horde::logMessage('Wicked_Driver_sql::removeAllAttachments: ' . $sql, 'DEBUG');

        $result = $this->_db->query($sql, $params);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Now try against the attachment history table. $params is
         * unchanged. */
        $sql = 'DELETE FROM ' . $this->_params['attachmenthistorytable'] .
            ' WHERE page_id = ?';

        Horde::logMessage('Wicked_Driver_sql::removeAllAttachments: ' . $sql, 'DEBUG');

        return $this->_db->query($sql, $params);
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
     * @return boolean  The new version of the file attached, or a PEAR_Error::
     *                  instance on failure.
     */
    function _attachFile($file)
    {
        $where = 'page_id = ' . $this->_db->quote($file['page_id']) .
                 ' AND attachment_name = ' . $this->_db->quote($file['attachment_name']);
        $attachments = $this->_retrieve($this->_params['attachmenttable'], $where);
        if (is_a($attachments, 'PEAR_Error')) {
            Horde::logMessage($attachments, 'ERR');
            return $attachments;
        }

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
                           $this->_db->quote($file['attachment_name']));
            $result = $this->_db->query($sql);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }

            $sql = sprintf('UPDATE %s SET attachment_majorversion = %s, attachment_minorversion = %s, change_log = %s, change_author = %s, attachment_created = %s WHERE page_id = %d AND attachment_name = %s',
                           $this->_params['attachmenttable'],
                           intval($majorversion),
                           intval($minorversion),
                           $this->_db->quote($this->_convertToDriver($file['change_log'])),
                           $this->_db->quote($this->_convertToDriver($file['change_author'])),
                           intval(time()),
                           intval($file['page_id']),
                           $this->_db->quote($this->_convertToDriver($file['attachment_name'])));
            $result = $this->_db->query($sql);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        } else {
            $majorversion = 1;
            $minorversion = 0;
            $sql = sprintf('INSERT INTO %s (page_id, attachment_majorversion, attachment_minorversion, change_log, change_author, attachment_created, attachment_name) VALUES (%d, 1, 0, %s, %s, %s, %s)',
                           $this->_params['attachmenttable'],
                           intval($file['page_id']),
                           $this->_db->quote($this->_convertToDriver($file['change_log'])),
                           $this->_db->quote($this->_convertToDriver($file['change_author'])),
                           intval(time()),
                           $this->_db->quote($this->_convertToDriver($file['attachment_name'])));
            $result = $this->_db->query($sql);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return (int)$majorversion . '.' . (int)$minorversion;
    }

    /**
     * Log a hit to $pagename.
     *
     * @param string $pagename  The page that was viewed.
     *
     * @return mixed  True or PEAR_Error on failure.
     */
    function logPageView($pagename)
    {
        $query = 'UPDATE ' . $this->_params['table'] .
                 ' SET page_hits = page_hits + 1 WHERE page_name = ?';
        $values = array($this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::logPageView(' . $pagename . '): ' . $query, 'DEBUG');

        return $this->_db->query($query, $values);
    }

    /**
     * Creates a new page.
     *
     * @param string $pagename  The new page's name.
     * @param string $text      The new page's text.
     *
     * @return mixed  True, or PEAR_Error on failure.
     */
    function newPage($pagename, $text)
    {
        if (!strlen($pagename)) {
            return PEAR::raiseError(_("Page name must not be empty"));
        }

        if ($GLOBALS['browser']->isRobot()) {
            return PEAR::raiseError(_("Robots are not allowed to create pages"));
        }

        $author = $GLOBALS['registry']->getAuth();
        if ($author === false) {
            $author = null;
        }

        $page_id = $this->_db->nextId($this->_params['table']);
        if (is_a($page_id, 'PEAR_Error')) {
            Horde::logMessage($page_id, 'ERR');
            return $page_id;
        }

        $query = 'INSERT INTO ' . $this->_params['table'] . ' ' .
                 '(page_id, page_name, page_text, ' .
                 'version_created, page_majorversion, ' .
                 'page_minorversion, page_hits, change_author) ' .
                 'VALUES (?, ?, ?, ?, 1, 0, 0, ?)';
        $values = array($page_id,
                        $this->_convertToDriver($pagename),
                        $this->_convertToDriver($text),
                        time(),
                        $author);

        Horde::logMessage('Wicked_Driver_sql::newPage(): ' . $query, 'DEBUG');

        /* Attempt the insertion/update query. */
        $result = $this->_db->query($query, $values);

        /* Return an error immediately if the query failed. */
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

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
     * @return mixed  True or PEAR_Error on failure.
     */
    function renamePage($pagename, $newname)
    {
        $query = 'UPDATE ' . $this->_params['table'] .
                 ' SET page_name = ? WHERE page_name = ?';
        $values = array($this->_convertToDriver($newname), $this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::renamePage(): ' . $query, 'DEBUG');

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        $query = 'UPDATE ' . $this->_params['historytable'] .
                 ' SET page_name = ? WHERE page_name = ?';
        $values = array($this->_convertToDriver($newname), $this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::renamePage(): ' . $query, 'DEBUG');

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        $changelog = sprintf(_("Renamed page from %s"), $pagename);
        $newPage = $this->retrieveByName($newname);
        if (is_a($newPage, 'PEAR_Error')) {
            return $newPage;
        }

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

        $result = $this->_db->query($query, $values);

        /* Return an error immediately if the query failed. */
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
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

        /* Attempt the insertion/update query. */
        $result = $this->_db->query($query, $values);

        /* Return an error immediately if the query failed. */
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    function getPages($special = true, $no_cache = false)
    {
        static $pageNames;
        if (!isset($pageNames) || $no_cache) {
            $query = 'SELECT page_id, page_name FROM ' . $this->_params['table'];

            Horde::logMessage('Wicked_Driver_sql::getPages(): ' . $query, 'DEBUG');

            $result = $this->_db->getAssoc($query);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
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

        $result = $this->_db->getOne($query, $values);
        if ($result && !is_a($result, 'PEAR_Error')) {
            /* We're deleting the current version. Have to promote the
             * next-most revision from the history table. */
            $query = 'SELECT * FROM ' . $this->_params['historytable'] .
                     ' WHERE page_name = ? ORDER BY page_majorversion DESC, page_minorversion DESC';
            $query = $this->_db->modifyLimitQuery($query, 0, 1, array($this->_convertToDriver($pagename)));

            Horde::logMessage('Wicked_Driver_sql::removeVersion(): ' . $query, 'DEBUG');

            $revision = $this->_db->getRow($query, array($this->_convertToDriver($pagename)), DB_FETCHMODE_ASSOC);
            if (is_a($revision, 'PEAR_Error')) {
                Horde::logMessage($revision, 'ERR');
                return $revision;
            }

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

            $result = $this->_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }

            /* Finally, remove the version that we promoted from the
             * history table. */
            $query = 'DELETE FROM ' . $this->_params['historytable'] .
                ' WHERE page_name = ? and page_majorversion = ? and page_minorversion = ?';
            $values = array($this->_convertToDriver($pagename), $revision['page_majorversion'], $revision['page_minorversion']);

            Horde::logMessage('Wicked_Driver_sql::removeVersion(): ' . $query, 'DEBUG');

            $result = $this->_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        } else {
            /* Removing a historical revision - we can just slice it
             * out of the history table. $values is unchanged. */
            $query = 'DELETE FROM ' . $this->_params['historytable'] .
                ' WHERE page_name = ? and page_majorversion = ? and page_minorversion = ?';

            Horde::logMessage('Wicked_Driver_sql::removeVersion(): ' . $query, 'DEBUG');

            $result = $this->_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return true;
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

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        $query = 'DELETE FROM ' . $this->_params['historytable'] .
                 ' WHERE page_name = ?';
        $values = array($this->_convertToDriver($pagename));

        Horde::logMessage('Wicked_Driver_sql::removeAllVersions(): ' . $query, 'DEBUG');

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

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
            $query = $this->_db->modifyLimitQuery($query, 0, $limit);
        }

        Horde::logMessage('Wicked_Driver_sql::_retrieve(): ' . $query, 'DEBUG');

        $result = $this->_db->query($query);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if (is_a($row, 'PEAR_Error')) {
            return $row;
        }

        $pages = array();
        $index = 0;
        while ($row && !is_a($row, 'PEAR_Error')) {
            $pages[$index] = $row;
            if (isset($row['page_name'])) {
                $pages[$index]['page_name'] = $this->_convertFromDriver($row['page_name']);
            }
            if (isset($row['page_text'])) {
                $pages[$index]['page_text'] = $this->_convertFromDriver($row['page_text']);
            }
            if (isset($row['change_log'])) {
                $pages[$index]['change_log'] = $this->_convertFromDriver($row['change_log']);
            }

            /* Advance to the new row in the result set. */
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            $index++;
        }
        $result->free();

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
        return Horde_String::convertCharset($value, $this->getCharset());
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
        return Horde_String::convertCharset($value, $GLOBALS['registry']->getCharset(), $this->getCharset());
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success, PEAR_Error on failure.
     */
    function connect()
    {
        try {
            $this->_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('rw', 'wicked', 'storage');
        } catch (Horde_Exception $e) {
            return PEAR::raiseError($e->getMessage());
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
