<?php
/**
 * News base calss
 *
 * Copyright 2007 Obala d.o.o.
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: News.php 287 2008-01-25 17:45:33Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */
class News {

    const UNCONFIRMED = 0;
    const CONFIRMED = 1;
    const LOCKED = 2;
    const VFS_PATH = '.horde/news';

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    private $_params = array();

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
    public $writedb;

    /**
     * Handle for the tables prefix.
     *
     * @var prefix
     */
    public $prefix = 'news';

    /**
     */
    public function __construct()
    {
        $this->_params = Horde::getDriverConfig('storage', 'sql');
        $this->_connect();
    }

    /**
     * Returns the current language
     *
     * @return string  The current language.
     */
    static public function getLang()
    {
        global $conf;

        static $lang;

        if ($lang === null) {
            $lang = NLS::Select();
            if (!empty($conf['attributes']['languages']) &&
                !in_array($lang, $conf['attributes']['languages'])) {
                $lang = $conf['attributes']['languages'][0];
            }
        }

        return $lang;
    }

    /**
     * Returns a flag image for a language.
     *
     * @param string $lang  The language to return the flag for, e.g. 'en_US'.
     */
    static public function getFlag($lang)
    {
        $flag = 'flags/' . strtolower(substr($lang, -2)) . '.png';
        return Horde::img($flag, $lang, 'align="middle"', $GLOBALS['registry']->getImageDir('horde'));
    }

    /**
     * Load trackback object
     *
     * @param array $data Data to pass to the communication
     */
    static public function loadTrackback($data = array())
    {
        include_once 'Services/Trackback.php';
        if (!class_exists('Services_Trackback')) {
            return PEAR::raiseError(_("Services/Trackback is not installed."));
        }

        $trackback_conf = $GLOBALS['conf']['trackback'];
        unset($trackback_conf['spamcheck']);
        $trackback_conf['httprequest'] = array(
            'allowRedirects'    => true,
            'maxRedirects'      => 2,
            'useragent'         => 'HORDE News'
        );

        return Services_Trackback::create($data, $trackback_conf);
    }

    /**
     * Template path
     *
     * @param intiger    $id $category id
     * @param string     $type browse/news
     *
     * @return string $template template path
     */
    static public function getTemplatePath($cid, $type)
    {
        $template = NEWS_TEMPLATES . '/' . $type . '/';
        if (file_exists($template .  $cid)) {
            $template .= $cid . '/';
        }

        return $template;
    }

    /**
     * Format file size
     *
     * @param int filesize
     *
     * @return boolean formatted filesize.
     */
    static public function format_filesize($filesize)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $pass = 0; // set zero, for Bytes
        while ($filesize >= 1024) {
            $filesize /= 1024;
            $pass++;
        }

        return round($filesize, 2) . ' ' . $units[$pass];
    }

    /**
     * Format file size
     *
     * @param int filesize
     *
     * @return boolean formatted filesize.
     */
    static public function format_attached($id)
    {
        global $mime_drivers, $mime_drivers_map;

        require_once 'Horde/MIME/Part.php';
        require_once 'Horde/MIME/Viewer.php';
        require_once 'Horde/MIME/Magic.php';
        require_once 'Horde/MIME/Contents.php';
        require HORDE_BASE . '/config/mime_drivers.php';

        $dowload_img = Horde::img('save.png', _("Dowload"), ' style="width: 16px height: 16px"', $GLOBALS['registry']->getImageDir('horde'));
        $dowload_zip = Horde::img('mime/compressed.png', _("Dowload Zip Compressed"), 'style="width: 16px height: 16px"', $GLOBALS['registry']->getImageDir('horde'));
        $view_url = Horde::applicationUrl('files.php');

        $html = '<table><tr valign="top"><td>';
        $html .= Horde::link(Util::addParameter($view_url, array('actionID' => 'dowload_zip', 'id' => $id)), _("Compress and dowload all files at once")) . $dowload_zip . '</a> ' . "\n";
        $html .= _("Attached files: ") . '</td><td>' . "\n";

        $sql = 'SELECT filename, filesize FROM ' . $this->prefix . '_attachment WHERE id = ? AND lang = ?';
        $files = $GLOBALS['news']->db->getAll($sql, array($id, NLS::select()),  DB_FETCHMODE_ASSOC);

        foreach ($files as $file_data) {
            $html .= ' -  ' . "\n";
            $file = basename($file_data['filename']);
            $dir = dirname($file_data['filename']);

            $html .= Horde::link(Util::addParameter($view_url, array('actionID' => 'dowload_zip', 'dir' => $dir, 'file' => $file)), sprintf(_("Compress and dowload %s"), $file)) . $dowload_zip . '</a> ' . "\n";
            $html .= Horde::link(Util::addParameter($view_url, array('actionID' => 'dowload_file', 'dir' => $dir, 'file' => $file)), sprintf(_("Dowload %s"), $file)) . $dowload_img . '</a> ' . "\n";
            $html .= Horde::link(Util::addParameter($view_url, array('actionID' => 'view_file', 'dir' => $dir, 'file' => $file)), sprintf(_("Preview %s"), $file), '', '_file_view');
            $html .= Horde::img(MIME_Viewer::getIcon(MIME_Magic::extToMIME(substr($file, strpos($file, '.')))), $file, 'width="16" height="16"', '') . ' ';
            $html .= $file . '</a> ' . "\n";

            $html .= ' (' . self::format_filesize($file_data['filesize']) . ')';
            $html .= '<br /> ' . "\n";
        }

        $html .= ' </td></tr></table>';

        return $html;
    }

    /**
     * Load VFS Backend
     */
    static public function loadVFS()
    {
        $v_params = Horde::getVFSConfig('images');
        if ($v_params instanceof PEAR_Error) {
            return $v_params;
        }

        require_once 'VFS.php';
        return VFS::singleton($v_params['type'], $v_params['params']);
    }

    /**
     * Store image
     *
     * @param $id     Image id (item id)
     * @param $file   Image file
     * @param $type   Image type ('events', 'categories' ...)
     * @param $resize Resize the big image?
     */
    static public function saveImage($id, $file, $type = 'news', $resize = true)
    {
        global $conf;

        if ($file['uploaded'] instanceof PEAR_Error) {
            return $file['uploaded'];
        }

        $vfs = self::loadVFS();
        if ($vfs instanceof PEAR_Error) {
            return $vfs;
        }

        $vfspath = self::VFS_PATH . '/images/' . $type;
        $vfs_name = $id . '.' . $conf['images']['image_type'];

        require_once 'Horde/Image.php';
        $img = Horde_Image::factory('gd', array('type' => $conf['images']['image_type'],
                                                'temp' => Horde::getTempDir()));

        if ($img instanceof PEAR_Error) {
            return $img;
        }

        $result = $img->loadFile($file);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        // Store big image for articles
        if ($type == 'news') {

            // Resize big image?
            if ($resize) {
                $dimensions = $img->getDimensions();
                if ($dimensions instanceof PEAR_Error) {
                    return $dimensions;
                }

                $resize = $img->resize(min($conf['images']['image_width'], $dimensions['width']),
                                       min($conf['images']['image_height'], $dimensions['height']));
                if ($resize instanceof PEAR_Error) {
                    return $resize;
                }
            }

            // Store big image
            $result = $vfs->writeData($vfspath . '/big/', $vfs_name, $img->raw(), true);
            if ($result instanceof PEAR_Error) {
                return $result;
            }
        }

        // Resize thumbnail
        $dimensions = $img->getDimensions();
        $resize = $img->resize(min($conf['images']['thumbnail_width'], $dimensions['width']),
                               min($conf['images']['thumbnail_height'], $dimensions['height']));
        if ($resize instanceof PEAR_Error) {
            return $resize;
        }

        // Trick path for articles
        if ($type == 'news') {
            $vfspath .= '/small';
        }

        // Store thumbnail
        return $vfs->writeData($vfspath, $vfs_name, $img->raw(), true);
    }

    /**
     * Delete image
     *
     * @param $id     Image id (item id)
     */
    static public function deleteImage($id)
    {
        global $conf;

        $vfs = NEWS::loadVFS();
        if ($vfs instanceof PEAR_Error) {
            return $vfs;
        }

        $vfspath = self::VFS_PATH . '/images/' . $type;
        $vfs_name = $id . '.' . $conf['images']['image_type'];

        $vfs->deleteFile($vfspath . '/small/', $vfs_name);
        $vfs->deleteFile($vfspath . '/big/', $vfs_name);
    }

    /**
     * get Image path
     */
    static public function getImageUrl($id, $view = 'small', $type = 'news')
    {
        if (empty($GLOBALS['conf']['images']['direct'])) {
            return Util::addParameter(Horde::applicationUrl('view.php'),
                                     array('type' => $type,
                                           'view' => $view,
                                           'id' => $id),
                                     null, false);
        } else {
            return $GLOBALS['conf']['images']['direct'] .
                   '/' . $type . '/' . $view . '/' .
                   $id . '.' . $GLOBALS['conf']['images']['image_type'];
        }
    }

    /**
     * get gallery images
     */
    static public function getGalleyImages($id)
    {
        $images = $GLOBALS['cache']->get("news_gallery_$id", 0);
        if ($images) {
            return unserialize($images);
        }

        $images = $GLOBALS['registry']->call('images/listImages', array('ansel', $id, PERMS_SHOW, 'thumb'));
        $GLOBALS['cache']->set("news_gallery_$id", serialize($images));

        return $images;
    }

    /**
     * Formats time according to user preferences.
     *
     * @param int $timestamp  Message timestamp.
     *
     * @return string  Formatted date.
     */
    static public function dateFormat($timestamp)
    {
        static $df, $tf;

        if ($df === null) {
            $df = $GLOBALS['prefs']->getValue('date_format');
            $tf = $GLOBALS['prefs']->getValue('twentyFour');
        }

        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        return strftime($df, $timestamp)
            . ' '
            . (date($tf ? 'G:i' : 'g:ia', $timestamp));
    }

    /**
     * Fomates time accoring to user prefs
     *
     * @param int $timestamp message timestamp
     *
     * @return string $date fromatted date
     */
    public function datetimeParams()
    {
        static $params;

        if (!is_array($params)) {
            $sql = 'SELECT MIN(YEAR(publish)) FROM ' . $this->prefix;
            $params = array('start_year' => $GLOBALS['news']->db->getOne($sql),
                            'end_year' => date('Y') + 1,
                            'picker' => true, 
                            'format_in' => '%Y-%m-%d %H:%M:%S',
                            'format_out' => '%Y-%m-%d %H:%M:%S');
        }

        return $params;
    }

    /**
     * Get last submitted comments
     *
     * @param int $limit    How many comments to show
     */
    static public function getLastComments($limit = 10)
    {
        $cache_key = 'news_lastcommetns_' . $limit;
        $threads = $GLOBALS['cache']->get($cache_key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($threads) {
            return unserialize($threads);
        }

        global $registry;

        if (!$registry->hasMethod('forums/getForumName')) {
            return PEAR::raiseError(_("Comments are not supported."));
        }

        $params = array(0, 'message_timestamp', 1, false, 'news', null, 0, $limit);
        $threads = $registry->call('forums/getThreads', $params);
        if ($threads instanceof PEAR_Error) {
            return $threads;
        }

        $read_url = Horde::applicationUrl('news.php', true);
        foreach ($threads as $id => $message) {
            $news_id = $registry->call('forums/getForumName', array('news', $message['forum_id']));
            if ($news_id instanceof PEAR_Error) {
                unset($threads[$id]);
                continue;
            }

            $threads[$id]['news_id'] = $news_id;
            $threads[$id]['read_url'] = Util::addParameter($read_url, 'id', $news_id);
        }

        $GLOBALS['cache']->set($cache_key, serialize($threads));
        return $threads;
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
        return $this->writedb->query('UPDATE ' . $this->prefix . ' SET comments = ? WHERE id = ?', array($count, $id));
    }

    /**
     * Get news
     *
     * @param int    $news news id
     *
     * @return true on succes PEAR_Error on failure
     */
    public function get($id)
    {
        // Admins bypass the cache (can read nonpublished and locked news)
        if (!Auth::isAdmin('news:admin')) {
            $key = 'news_'  . self::getLang() . '_' . $id;
            $data = $GLOBALS['cache']->get($key, $GLOBALS['conf']['cache']['default_lifetime']);
            if ($data) {
                return unserialize($data);
            }
        }

        $query = 'SELECT n.publish, n.user, n.source, n.sourcelink, n.category1, n.parents, ' .
                ' n.category2, n.attachments, n.picture, n.comments, n.gallery, n.sponsored, ' .
                ' l.title, l.content, l.picture_comment, l.tags, n.selling, n.trackbacks, n.threads, ' .
                ' n.form_id, n.form_ttl FROM ' . $this->prefix . ' AS n, ' . $this->prefix . '_body AS l ' .
                ' WHERE n.id=? AND n.id=l.id AND l.lang=?';

        if (!Auth::isAdmin('news:admin')) {
            $query .= ' AND n.status = ' . self::CONFIRMED;
        }

        $data = $this->db->getRow($query, array($id, self::getLang()), DB_FETCHMODE_ASSOC);
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
            $data['parents'] = $this->db->getAssoc($sql, false, array(self::getLang()), DB_FETCHMODE_ASSOC);
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

        if (!Auth::isAdmin('news:admin')) {
            $GLOBALS['cache']->set($key, serialize($data));
        }

        return $data;
    }

    /**
     * Updates news comments counter
     *
     * @param int    $news news id
     *
     * @return true on succes PEAR_Error on failure
     */
    public function getFiles($id)
    {
        $sql = 'SELECT filename, filesize FROM ' . $this->prefix . '_attachment WHERE id=? AND lang=?';
        return $this->db->getAll($sql, array($id, self::getLang()),  DB_FETCHMODE_ASSOC);
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
        $sql = 'SELECT version, created, user_uid,content,action FROM ' . $this->prefix . '_versions WHERE id = ? ORDER BY version DESC';
        return $this->db->getAll($sql, array($id), DB_FETCHMODE_ASSOC);
    }

    /**
     * Logs a news view.
     *
     * @return boolean True, if the view was logged, false if the message was aleredy seen
     */
    function logView($id)
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
                  $GLOBALS['conf']['cookie']['domain'],  $GLOBALS['conf']['use_ssl'] == 1 ? 1 : 0);

        /* Update the count */
        $sql = 'UPDATE ' . $this->prefix . ' SET view_count = view_count + 1 WHERE id = ?';
        $result = $this->writedb->query($sql, array($id));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        /* Log it */
        $sql = 'INSERT INTO ' . $this->prefix . '_user_reads (id,user,ip,useragent,readdate) VALUES (?,?,?,?,NOW())';
        $result = $this->writedb->query($sql, array($id,Auth::getAuth(),$_SERVER['REMOTE_ADDR'],$_SERVER["HTTP_USER_AGENT"]));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return true;
    }

    /**
     * Remove from shop recursively by category ID: categories, articles, stocks and images
     *
     * @param mixed int Category ID
     *
     * @return mixed TRUE or PEAR error
     */
    public function removeByCategory($cid)
    {
        /*vse v kategoriji in pol delete povsot */
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
        $result = $this->writedb->query($sql, $params);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        /* Update trackback count */
        $GLOBALS['cache']->expire('news_'  . self::getLang() . '_' . $id);
        return $this->writedb->query('UPDATE ' . $this->prefix . ' SET trackbacks = trackbacks + 1 WHERE id = ?', array($id));
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
        return $this->writedb->query($sql, array($id));
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
            $this->writedb->query($sql, array(1, $info['source_id']));
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
        $new_id = $this->writedb->nextId('news_sources');

        $sql = 'INSERT INTO ' . $this->prefix . '_sources' .
               ' (source_id, source_name, source_url)' .
               ' VALUES (?, ?, ?)';
        $values = array($new_id,
                        $data['source_name'],
                        $data['source_url']);

        $source = $this->writedb->query($sql, $values);
        if ($source instanceof PEAR_Error) {
            Horde::logMessage($source, __FILE__, __LINE__, PEAR_LOG_ERR);
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

        $source = $this->writedb->query($sql, $values);
        if ($source instanceof PEAR_Error) {
            Horde::logMessage($source, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $source;
        }
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success; exits (Horde::fatal()) on error.
     */
    private function _connect()
    {
        Horde::assertDriverConfig($this->_params, 'storage',
                                  array('phptype', 'charset'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }
        if (isset($this->_params['prefix'])) {
            $this->prefix = $this->_params['prefix'];
        }

        /* Connect to the SQL server using the supplied parameters. */
        require_once 'DB.php';
        $this->writedb = &DB::connect($this->_params,
                                        array('persistent' => !empty($this->_params['persistent'])));
        if ($this->writedb instanceof PEAR_Error) {
            Horde::fatal($this->writedb, __FILE__, __LINE__);
        }

        // Set DB portability options.
        switch ($this->writedb->phptype) {
        case 'mssql':
            $this->writedb->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->writedb->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->db = &DB::connect($params,
                                      array('persistent' => !empty($params['persistent'])));
            if ($this->db instanceof PEAR_Error) {
                Horde::fatal($this->db, __FILE__, __LINE__);
            }

            // Set DB portability options.
            switch ($this->db->phptype) {
            case 'mssql':
                $this->db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->db =& $this->writedb;
        }

        return true;
    }

   /**
     * Build whare search
     */
    public function buildQuery($perms = PERMS_READ, $criteria = array())
    {
        static $parts;

        $id = serialize($criteria);
        if (isset($parts[$id])) {
            return $parts[$id];
        }

        $sql = 'FROM ' . $GLOBALS['news']->prefix . ' AS n, ' . $GLOBALS['news']->prefix . '_body AS l '
            . ' WHERE n.id = l.id AND l.lang = ?';
        $params = array('_lang' => NLS::select());

        if ($perms == PERMS_READ) {
            $sql .= ' AND n.publish <= ? ';
            $params['_perms'] = date('Y-m-d H:i:s');
            $sql .= ' AND n.status = ? ';
            $params['_status'] = self::CONFIRMED;
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
    public function countNews($criteria = array(), $perms = PERMS_READ)
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
    public function listNews($criteria = array(), $from = 0, $count = 0, $perms = PERMS_READ)
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
            // return $cloud;
        }

        $sql = 'SELECT l.tags, n.publish FROM ' . $this->prefix . '_body AS l, '
               . $this->prefix . ' AS n WHERE l.lang = ? AND n.id = l.id AND n.status = ? ORDER BY n.publish DESC LIMIT 0, '
               . ($minimize ? '100' : '500');

        $result = $this->db->query($sql, array(NLS::select(), self::CONFIRMED));
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
        require_once dirname(__FILE__) . '/TagCloud.php';
        $tags = new Oscar_TagCloud();
        $tag_link = Horde::applicationUrl('search.php');
        foreach ($tags_elemets as $tag => $time) {
            sort($time);
            $tags->addElement($tag, Util::addParameter($tag_link, array('word' => $tag)),
                              count($tags_elemets[$tag]), $time[0]);
        }

        $cloud = $tags->buildHTML();
        $GLOBALS['cache']->set($cache_key, $cloud);

        return $cloud;
    }

    /**
     * Build News's list of menu articles
     */
    static public function getMenu($returnType = 'object')
    {
        require_once 'Horde/Menu.php';

        $menu = &new Menu();
        $img_dir = $GLOBALS['registry']->getImageDir('horde');

        if ($GLOBALS['prefs']->getValue('news_layout') != '') {
            $menu->add(Horde::applicationUrl('content.php'), _("Overview"), 'layout.png', $img_dir);
        }
        $menu->add(Horde::applicationUrl('browse.php'), _("Archive"), 'info.png', $img_dir);
        $menu->add(Horde::applicationUrl('search.php'), _("Search"), 'search.png', $img_dir);
        $menu->add(Horde::applicationUrl('add.php'), _("Add"), 'edit.png', $img_dir);

        if ($GLOBALS['conf']['attributes']['tags']) {
            $menu->add(Horde::applicationUrl('cloud.php'), _("Tag cloud"), 'colorpicker.png', $img_dir);
        }

        if (Auth::isAdmin('news:admin')) {
            $menu->add(Horde::applicationUrl('edit.php'), _("Editorship"), 'config.png', $img_dir);
            $menu->add(Horde::applicationUrl('admin/categories/index.php'), _("Administration"), 'administration.png', $img_dir);
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

}
