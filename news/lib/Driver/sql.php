<?php
/**
 * $Id: News.php 1263 2009-02-01 23:25:56Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license inion (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
class News_Driver_sql extends News_Driver {

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    public $db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $db if a separate write database is not required.
     *
     * @var DB
     */
    public $write_db;

    /**
     * Handle for the tables prefix.
     *
     * @var prefix
     */
    public $prefix = 'news';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_params = Horde::getDriverConfig('storage', 'sql');
        $this->_connect();
    }

    /**
     * Updates schedul comments counter
     *
     * @param int $id schedul id
     *
     * @return true on succes PEAR_Error on failure
     */
    public function updateComments($id, $count)
    {
        return $this->write_db->query('UPDATE ' . $this->prefix . ' SET comments = ? WHERE id = ?', array($count, $id));
    }

    /**
     * Get news
     *
     * @param int    $news news id
     *
     * @return true on succes PEAR_Error on failure
     */
    protected function _get($id)
    {
        $query = 'SELECT n.publish, n.user, n.source, n.sourcelink, n.category1, n.parents, ' .
                ' n.category2, n.attachments, n.picture, n.comments, n.gallery, n.sponsored, ' .
                ' l.title, l.content, l.picture_comment, l.tags, n.selling, n.trackbacks, n.threads, ' .
                ' n.form_id, n.form_ttl FROM ' . $this->prefix . ' AS n, ' . $this->prefix . '_body AS l ' .
                ' WHERE n.id = ? AND n.id=l.id AND l.lang = ?';

        /** TODO Allow for now to allow static linked news, but not shown in list
        if (!$registry->isAdmin(array('permission' => 'news:admin'))) {
            $query .= ' AND n.status = ' . News::CONFIRMED;
        }
        */

        $data = $this->db->getRow($query, array($id, News::getLang()), DB_FETCHMODE_ASSOC);
        if ($data instanceof PEAR_Error) {
            return $data;
        }

        if (empty($data)) {
            return PEAR::raiseError(sprintf(_("There requested news %s don't exist."), $id));
        }

        /* Get talks backs */
        if ($data['trackbacks']) {
            $sql = 'SELECT excerpt, created, title, url, blog_name FROM ' . $this->prefix . '_trackback WHERE id = ?';
            $data['trackback'] = $this->db->getAll($sql, array($id), DB_FETCHMODE_ASSOC);
            if ($data['trackback'] instanceof PEAR_Error) {
                return $data['trackback'];
            }
        }

        /* Get parents */
        if ($data['parents']) {
            $sql = 'SELECT n.id, n.publish, n.comments, l.title ' .
                ' FROM ' . $this->prefix . ' AS n, ' . $this->prefix . '_body AS l ' .
                ' WHERE n.id IN (' . $data['parents'] . ') AND n.id = l.id AND l.lang = ?';
            $data['parents'] = $this->db->getAssoc($sql, false, array(News::getLang()), DB_FETCHMODE_ASSOC);
            if ($data['parents'] instanceof PEAR_Error) {
                return $data['parents'];
            }
        }

        /* Get threads */
        if ($data['threads']) {
            $sql = 'SELECT message_id, forum_id, message_subject, message_seq ' .
                ' FROM agora_messages WHERE message_id IN (' . $data['threads'] . ')';
            $data['threads'] = $this->db->getAssoc($sql, false, null, DB_FETCHMODE_ASSOC);
            if ($data['threads'] instanceof PEAR_Error) {
                return $data['threads'];
            }
        }

        return $data;
    }

    /**
     * Get news attached files
     *
     * @param int $news_id      news id
     * @param string $news_lang news language
     *
     * @return true on succes PEAR_Error on failure
     */
    public function getFiles($news_id, $news_lang = null)
    {
        if (is_null($news_lang)) {
            $news_lang = News::getLang();
        }

        $sql = 'SELECT file_id, news_id, news_lang, file_name, file_size, file_type FROM ' . $this->prefix . '_files'
                . ' WHERE news_id = ? AND news_lang = ?';

        return $this->db->getAll($sql, array($news_id, $news_lang), DB_FETCHMODE_ASSOC);
    }

    /**
     * Get version
     *
     * @param intiger    $id news id
     * @param array      $info array with all news info
     *
     * @return result of the insert
     */
    public function getVerison($id, $version)
    {
        $sql = 'SELECT id, created, user_uid, content FROM ' . $this->prefix . '_versions WHERE id = ? AND version = ?';
        $result = $this->db->getRow($sql, array($id, $version), DB_FETCHMODE_ASSOC);
        $result['content'] = unserialize($result['content']);
        return $result;
    }

    /**
     * Get versions
     *
     * @param intiger    $id news id
     * @param array      $info array with all news info
     *
     * @return result of the insert
     */
    public function getVerisons($id)
    {
        $sql = 'SELECT version, created, user_uid, content, action FROM ' . $this->prefix . '_versions WHERE id = ? ORDER BY version DESC';
        return $this->db->getAll($sql, array($id), DB_FETCHMODE_ASSOC);
    }

    /**
     * Logs a news view.
     *
     * @return boolean True, if the view was logged, false if the message was aleredy seen
     */
    public function logView($id)
    {
        if ($GLOBALS['browser']->isRobot()) {
            exit;
        }

        /* We already read this story? */
        if (isset($_COOKIE['news_viewed_news']) &&
            strpos($_COOKIE['news_viewed_news'], ':' . $id . '|') !== false) {
            return false;
        }

        /* Rembember when we see a story */
        if (!isset($_COOKIE['news_viewed_news'])) {
            $_COOKIE['news_viewed_news'] = ':';
        }
        $_COOKIE['news_viewed_news'] .= $id . '|' . $_SERVER['REQUEST_TIME'] . ':';

        setcookie('news_viewed_news', $_COOKIE['news_viewed_news'], $_SERVER['REQUEST_TIME'] + 22896000, $GLOBALS['conf']['cookie']['path'],
                  $GLOBALS['conf']['cookie']['domain'], $GLOBALS['conf']['use_ssl'] == 1 ? 1 : 0);

        /* Update the count */
        $sql = 'UPDATE ' . $this->prefix . ' SET view_count = view_count + 1 WHERE id = ?';
        $result = $this->write_db->query($sql, array($id));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        /* Log it */
        $sql = 'INSERT INTO ' . $this->prefix . '_user_reads (id,user,ip,useragent,readdate) VALUES (?, ?, ? , ?, NOW())';
        $result = $this->write_db->query($sql, array($id, $GLOBALS['registry']->getAuth(), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return true;
    }

    /**
     * Attach a trackback
     */
    public function saveTrackback($id, $title, $url, $excerpt, $blog_name, $trackback_url)
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->prefix . '_trackback WHERE id = ? AND url = ?';
        $result = $this->db->getOne($sql, array($id, $url));
        if ($result > 0) {
            return PEAR::raiseError(sprintf(_("URL already trackbacked: %s"), $url));
        }

        $params = array('id' => $id,
                        'title' => $title,
                        'url' => $url,
                        'excerpt' => $excerpt,
                        'blog_name' => $blog_name,
                        'created' => date('Y-m-d H:i:s'));

        $sql = 'INSERT INTO ' . $this->prefix . '_trackback (' . implode(',', array_keys($params)) . ') VALUES (?, ?, ?, ?, ?, ?)';
        $result = $this->write_db->query($sql, $params);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        /* Update trackback count */
        $GLOBALS['cache']->expire('news_'  . News::getLang() . '_' . $id);
        return $this->write_db->query('UPDATE ' . $this->prefix . ' SET trackbacks = trackbacks + 1 WHERE id = ?', array($id));
    }

    /**
     * Delete a source
     *
     * @param integer $id  The source id to delete.
     *
     * @return boolean
     */
    public function deleteSource($id)
    {
        $GLOBALS['cache']->expire('newsSources');
        $this->deleteImage($id, 'sources');
        $sql = 'DELETE FROM ' . $this->prefix . '_sources WHERE sources_id = ?';
        return $this->write_db->query($sql, array($id));
    }

    /**
     * Fetches sources list
     *
     * @return array  An array containing all sources names
     */
    public function getSources($flat = false)
    {
        $sources = $GLOBALS['cache']->get('newsSources');
        if (empty($sources)) {
            $sql = 'SELECT source_id, source_name, source_url FROM ' . $this->prefix . '_sources ORDER BY source_name ASC';
            $sources = $this->db->getAssoc($sql, true, array(), DB_FETCHMODE_ASSOC);
            $GLOBALS['cache']->set('newsSources', serialize($sources));
        } else {
            $sources = unserialize($sources);
        }

        if (!$flat) {
            foreach ($sources as $source_id => $source) {
                $sources[$source_id] = $source['source_name'];
            }
        }

        return $sources;
    }

    /**
     * Save a source data into the backend from edit form.
     *
     * @param array $info  The source data to save.
     *
     * @return mixed  PEAR error.
     */
    public function saveSource($info)
    {
        /* Update/Insert source. */
        if (!empty($info['source_id'])) {
            $result = $this->_updateSource($info['source_id'], $info);
            if ($result instanceof PEAR_Error) {
                return $result;
            }
        } else {
            $info['source_id'] = $this->_insertSource($info);
            if ($info['source_id'] instanceof PEAR_Error) {
                return $info['source_id'];
            }
        }

        /* If image uploaded save to backend. */
        if (!empty($info['source_image']['name'])) {
            $image = $this->_saveImage($info['source_id'], $info['source_image']['file'], 'sources', $info['source_image_resize']);
            if ($image instanceof PEAR_Error) {
                return $image;
            }

            $sql = 'UPDATE ' . $this->prefix . '_sources SET source_image = ? WHERE source_id = ?';
            $this->write_db->query($sql, array(1, $info['source_id']));
        }

        $GLOBALS['cache']->expire('newsSources');
        return $info['source_id'];
    }

    /**
     * Insert source data.
     *
     * @param mixed $data  The source data to insert.
     *
     * @return array  Inserted ID or PEAR error.
     */
    private function _insertSource($data)
    {
        $new_id = $this->write_db->nextId('news_sources');

        $sql = 'INSERT INTO ' . $this->prefix . '_sources' .
               ' (source_id, source_name, source_url)' .
               ' VALUES (?, ?, ?)';
        $values = array($new_id,
                        $data['source_name'],
                        $data['source_url']);

        $source = $this->write_db->query($sql, $values);
        if ($source instanceof PEAR_Error) {
            Horde::logMessage($source, 'ERR');
            return $source;
        }

        return $new_id;
    }

    /**
     * Update source data.
     *
     * @param integer $source_id  The source id to update.
     * @param array   $data       The source data to update.
     *
     * @return array  NULL or PEAR error.
     */
    private function _updateSource($source_id, $data)
    {
        $sql = 'UPDATE ' . $this->prefix . '_sources' .
               ' SET source_name = ?, source_url = ?' .
               ' WHERE source_id = ?';
        $values = array($data['source_name'],
                        $data['source_url'],
                        $source_id);

        $source = $this->write_db->query($sql, $values);
        if ($source instanceof PEAR_Error) {
            Horde::logMessage($source, 'ERR');
            return $source;
        }
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    private function _connect()
    {
        $this->_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('read', 'news', 'storage');
        $this->_write_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('rw', 'news', 'storage');

        if (isset($this->_params['prefix'])) {
            $this->prefix = $this->_params['prefix'];
        }

        return true;
    }

   /**
     * Build whare search
     */
    public function buildQuery($perms = Horde_Perms::READ, $criteria = array())
    {
        static $parts;

        $id = serialize($criteria);
        if (isset($parts[$id])) {
            return $parts[$id];
        }

        $sql = 'FROM ' . $GLOBALS['news']->prefix . ' AS n, ' . $GLOBALS['news']->prefix . '_body AS l '
            . ' WHERE n.id = l.id AND l.lang = ?';
        $params = array('_lang' => $GLOBALS['registry']->preferredLang());

        if ($perms == Horde_Perms::READ) {
            $sql .= ' AND n.publish <= ? ';
            $params['_perms'] = date('Y-m-d H:i:s');
            $sql .= ' AND n.status = ? ';
            $params['_status'] = News::CONFIRMED;
        }

        if (empty($criteria)) {
            $parts[$id] = array($sql, $params);
            return $parts[$id];
        }

        /* check status */
        if (isset($criteria['status'])) {
            $sql .= ' AND n.status = ?';
            $params['status'] = $criteria['status'];
        }

        /* check status */
        if (isset($criteria['source'])) {
            $sql .= ' AND n.source = ?';
            $params['source'] = $criteria['source'];
        }

        /* get category */
        if (isset($criteria['category'])) {
            $sql .= ' AND (n.category1 = ? OR n.category2 = ?)';
            $params['category'] = $criteria['category'];
            $params['_category'] = $criteria['category'];
        }

        /* seaching for a pericolar word */
        if (isset($criteria['word'])) {
            $sql .= ' AND (l.title LIKE ? OR l.content LIKE ? OR l.tags LIKE ?)';
            $params['word'] = '%' . $criteria['word'] . '%';
            $params['_word'] = '%' . $criteria['word'] . '%';
            $params['tags'] = '%' . $criteria['word'] . '%';
        }

        /* submitter */
        if (isset($criteria['user'])) {
            $sql .= ' AND n.user = ? ';
            $params['user'] = $criteria['user'];
        }

        /* editor */
        if (isset($criteria['editor'])) {
            $sql .= ' AND n.editor = ? ';
            $params['editor'] = $criteria['editor'];
        }

        /* publish time */
        if (isset($criteria['published_to'])) {
            $sql .= ' AND n.publish <= ? ';
            $params['published_to'] =  $criteria['published_to'];
        }

        if (isset($criteria['published_from'])) {
            $sql .= ' AND n.publish >= ? ';
            $params['published_from'] =  $criteria['published_from'];
        }

        $parts[$id] = array($sql, $params);

        return $parts[$id];
    }

    /**
     * Count news
     *
     * @param array $criteria Filter parameter

     * @param int $perms Permissions filter
     *
     * @return Nimber of news
     */
    public function countNews($criteria = array(), $perms = Horde_Perms::READ)
    {
        $binds = $this->buildQuery($perms, $criteria);
        $binds[0] = 'SELECT COUNT(*) ' . $binds[0];

        return $this->db->getOne($binds[0], $binds[1]);
    }

    /**
     * List news
     *
     * @param array $criteria Filter parameter
     * @param int $from Offset
     * @param int $count Limit rows
     * @param int $perms Permissions filter
     *
     * @return array of news data
     */
    public function listNews($criteria = array(), $from = 0, $count = 0, $perms = Horde_Perms::READ)
    {
        $binds = $this->buildQuery($perms, $criteria);

        if (!isset($criteria['sort_by'])) {
            $criteria['sort_by'] = 'n.publish';
        }
        if (!isset($criteria['sort_dir'])) {
            $criteria['sort_dir'] = 'DESC';
        }

        $binds[0] = 'SELECT n.id, n.publish, n.user, n.category1, n.category2, n.comments, '
                    . ' n.picture, n.chars, l.title, l.abbreviation ' . $binds[0]
                    . ' ORDER BY ' . $criteria['sort_by']
                    . ' ' . $criteria['sort_dir'];

        if ($count) {
            $binds[0] = $this->db->modifyLimitQuery($binds[0], $from, $count);
        }

        return $this->db->getAll($binds[0], $binds[1], DB_FETCHMODE_ASSOC);
    }

    /**
     * Construct tag cloud
     *
     * @param boolean $minimize  Minimize tag cloud
     *                          (remove 1 length strings, and single occurrence)
     *
     * @return mixed  The HTML for the tag cloud | PEAR_Error
     */
    public function getCloud($minimize = false)
    {
        $cache_key = 'news_cloud_' . $minimize;
        $cloud = $GLOBALS['cache']->get($cache_key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($cloud) {
            return $cloud;
        }

        $sql = 'SELECT l.tags, n.publish FROM ' . $this->prefix . '_body AS l, '
               . $this->prefix . ' AS n WHERE l.lang = ? AND n.id = l.id AND n.status = ? ORDER BY n.publish DESC';

        $result = $this->db->limitQuery($sql, 0, ($minimize ? '100' : '500'), array($GLOBALS['registry']->preferredLang(), News::CONFIRMED));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $tags_elemets = array();
        while ($news = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            foreach (explode(' ', $news['tags']) as $tag) {
                if ($minimize && strlen($tag) < 2) {
                    continue;
                }
                $tags_elemets[$tag][] = strtotime($news['publish']);
            }
        }

        if ($minimize) {
            foreach ($tags_elemets as $tag => $content) {
                if (count($content) == 1) {
                    unset($tags_elemets[$tag]);
                }
            }
        }

        if (empty($tags_elemets)) {
            return '';
        }

        $i = 0;
        $tags = new News_TagCloud();
        $tag_link = Horde::applicationUrl('search.php');
        foreach ($tags_elemets as $tag => $time) {
            sort($time);
            $tags->addElement($tag, Horde_Util::addParameter($tag_link, array('word' => $tag)),
                              count($tags_elemets[$tag]), $time[0]);
        }

        $cloud = $tags->buildHTML();
        $GLOBALS['cache']->set($cache_key, $cloud);

        return $cloud;
    }

}
