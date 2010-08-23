<?php
/**
 * News base class
 *
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
class News {

    const UNCONFIRMED = 0;
    const CONFIRMED = 1;
    const LOCKED = 2;

    const VFS_PATH = '.horde/news';

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
            $lang = $GLOBALS['registry']->preferredLang();
            if (!empty($conf['attributes']['languages']) &&
                !in_array($lang, $conf['attributes']['languages'])) {
                $lang = $conf['attributes']['languages'][0];
            }
        }

        return $lang;
    }

    /**
     * Returns a flag image for a country.
     *
     * @param string $lang  The language to return the flag for, e.g. 'us'.
     */
    static public function getFlag($country)
    {
        $flag = 'flags/' . strtolower(substr($country, -2)) . '.png';
        return Horde::img($flag, $country);
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
     * Return a properly formatted link depending on the global pretty url
     * configuration
     *
     * @param string $controller       The controller to generate a URL for.
     * @param array $data              The data needed to generate the URL.
     * @param boolean $full            Generate a full URL.
     * @param integer $append_session  0 = only if needed, 1 = always,
     *                                 -1 = never.
     *
     * @param string  The generated URL
     */
    static public function getUrlFor($controller, $data, $full = false, $append_session = 0)
    {
        switch ($controller) {

        case 'news':
            if (empty($GLOBALS['conf']['urls']['pretty'])) {
                return Horde_Util::addParameter(Horde::applicationUrl('news.php', $full, $append_session), 'id', $data);
            } else {
                return Horde::applicationUrl('article/' . $data, $full, $append_session);
            }

        case 'category':
            if (empty($GLOBALS['conf']['urls']['pretty'])) {
                return Horde_Util::addParameter(Horde::applicationUrl('browse.php', $full, $append_session), 'category', $data);
            } else {
                return Horde::applicationUrl('category/' . $data, $full, $append_session);
            }

        case 'source':
            if (empty($GLOBALS['conf']['urls']['pretty'])) {
                return Horde_Util::addParameter(Horde::applicationUrl('browse.php', $full, $append_session), 'source', $data);
            } else {
                return Horde::applicationUrl('source/' . $data, $full, $append_session);
            }
        }
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
     * @param int $size File size
     *
     * @return boolean formatted file_size.
     */
    static public function format_filesize($size)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $pass = 0; // set zero, for Bytes
        while ($size >= 1024) {
            $size /= 1024;
            $pass++;
        }

        return round($size, 2) . ' ' . $units[$pass];
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
     * Format file size
     *
     * @param int $id News ID
     *
     * @return boolean formatted file_size.
     */
    public function format_attached($id)
    {
        $files = $GLOBALS['news']->getFiles($id);
        if (empty($files)) {
            return '';
        }

        if ($GLOBALS['registry']->isAdmin(array('permission' => 'news:admin'))) {
            $delete_img = Horde::img('delete.png', _("Delete"), ' style="width: 16px height: 16px"');
            $delete_url = Horde::applicationUrl('delete_file.php');
        }

        $dowload_img = Horde::img('save.png', _("Dowload"), ' style="width: 16px height: 16px"');
        $dowload_zip = Horde::img('mime/compressed.png', _("Dowload Zip Compressed"), 'style="width: 16px height: 16px"');
        $view_url = Horde::applicationUrl('files.php');

        $html = '<table><tr valign="top"><td>';
        $html .= Horde::link(Horde_Util::addParameter($view_url, array('actionID' => 'download_zip_all', 'news_id' => $id)), _("Compress and dowload all files at once")) . $dowload_zip . '</a> ' . "\n";
        $html .= _("Attached files: ") . '</td><td>' . "\n";

        foreach ($files as $file) {
            $view_url = Horde_Util::addParameter($view_url, $file);
            $html .= ' -  ' . "\n";
            $html .= Horde::link(Horde_Util::addParameter($view_url, 'actionID', 'download_zip'), sprintf(_("Compress and dowload %s"), $file['file_name'])) . $dowload_zip . '</a> ' . "\n";
            $html .= Horde::link(Horde_Util::addParameter($view_url, 'actionID', 'download_file'), sprintf(_("Dowload %s"), $file['file_name'])) . $dowload_img . '</a> ' . "\n";
            $html .= Horde::link(Horde_Util::addParameter($view_url, 'actionID', 'view_file'), sprintf(_("Preview %s"), $file['file_name']), '', '_file_view');
            $html .= Horde::img($GLOBALS['injector']->getInstance('Horde_Mime_Viewer')->getIcon($file['file_type']), $file['file_name'], 'width="16" height="16"', '') . ' ';
            if ($GLOBALS['registry']->isAdmin(array('permission' => 'news:admin'))) {
                $html .= Horde::link(Horde_Util::addParameter($delete_url, $file), sprintf(_("Delete %s"), $file['file_name'])) . $delete_img . '</a> ' . "\n";
            }
            $html .= $file['file_name'] . '</a> ' . "\n";
            $html .= ' (' . self::format_filesize($file['file_size']) . ')';
            $html .= '<br /> ' . "\n";
        }

        $html .= ' </td></tr></table>';

        return $html;
    }

    /**
     * Store image
     *
     * @param $id     Image owner record id
     * @param $file['file_name']   Horde_Form_Type_image::getInfo() result
     * @param $type   Image type ('events', 'categories' ...)
     * @param $resize Resize the big image?
     */
    static public function saveImage($id, $file, $type = 'news', $resize = true)
    {
        global $conf;

        $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images');
        $vfspath = self::VFS_PATH . '/images/' . $type;
        $vfs_name = $id . '.' . $conf['images']['image_type'];

        $context = array('tmpdir' => Horde::getTempDir());
        if (!empty($conf['image']['convert'])) {
            $context['convert'] = $conf['image']['convert'];
            $context['identify'] = $conf['image']['identify'];
        }
        $params = array('type' => $conf['images']['image_type'],
                        'context' => $context);
        $driver = $conf['image']['driver'];
        $img = Horde_Image::factory($driver, $params);
        $result = $img->loadFile($file);

        // Store big image for articles
        if ($type == 'news') {

            // Store full image
            $vfs->writeData($vfspath . '/full/', $vfs_name, $img->raw(), true);

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
            $vfs->writeData($vfspath . '/big/', $vfs_name, $img->raw(), true);
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
        $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images');
        $vfs_name = $id . '.' . $GLOBALS['conf']['images']['image_type'];
        $vfs->deleteFile(self::VFS_PATH . '/images/news/full', $vfs_name);
        $vfs->deleteFile(self::VFS_PATH . '/images/news/small', $vfs_name);
        $vfs->deleteFile(self::VFS_PATH . '/images/news/big', $vfs_name);
    }

    /**
     * Store file
     *
     * @param $file_id    File id
     * @param $file_src   File path
     */
    static public function saveFile($file_id, $file_src)
    {
        $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images');
        $vfs->writeData(self::VFS_PATH . '/files/', $file_id, file_get_contents($file_src), true);
    }

    /**
     * Get file contents
     *
     * @param $file_id     File ID
     */
    static public function getFile($file_id)
    {
        $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images');
        $vfs->read(self::VFS_PATH . '/files/', $file_id);
    }

    /**
     * Delete file
     *
     * @param $id     File ID
     */
    static public function deleteFile($file_id)
    {
        $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images');
        if ($vfs->exists(self::VFS_PATH . '/files/', $file_id)) {
            $vfs->deleteFile(self::VFS_PATH . '/files/', $file_id);
        }
    }

    /**
     * Returns image path
     */
    static public function getImageUrl($id, $view = 'small', $type = 'news')
    {
        if (empty($GLOBALS['conf']['images']['direct'])) {
            return Horde_Util::addParameter(Horde::applicationUrl('view.php'),
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
     * Returns gallery images
     */
    static public function getGalleyImages($id)
    {
        $images = $GLOBALS['cache']->get("news_gallery_$id", 0);
        if ($images) {
            return unserialize($images);
        }

        $images = $GLOBALS['registry']->call('images/listImages', array('ansel', $id, Horde_Perms::SHOW, 'thumb'));
        $GLOBALS['cache']->set("news_gallery_$id", serialize($images));

        return $images;
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
            $sql = 'SELECT MIN(YEAR(publish)) FROM ' . $GLOBALS['news']->prefix;
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

        foreach ($threads as $id => $message) {
            try {
                $news_id = $registry->call('forums/getForumName', array('news', $message['forum_id']));
            } catch (Horde_Exception $e) {
                unset($threads[$id]);
                continue;
            }

            $threads[$id]['news_id'] = $news_id;
            $threads[$id]['read_url'] = self::getUrlFor('news', $news_id, true);
        }

        $GLOBALS['cache']->set($cache_key, serialize($threads));
        return $threads;
    }

    /**
     * Build News's list of menu articles
     */
    static public function getMenu()
    {
        $menu = new Horde_Menu();
        $img_dir = Horde_Themes::img(null, 'horde');

        if ($GLOBALS['prefs']->getValue('news_layout') != '') {
            $menu->add(Horde::applicationUrl('content.php'), _("Overview"), 'layout.png', $img_dir);
        }
        $menu->add(Horde::applicationUrl('browse.php'), _("Archive"), 'info.png', $img_dir);
        $menu->add(Horde::applicationUrl('search.php'), _("Search"), 'search.png', $img_dir);
        $menu->add(Horde::applicationUrl('add.php'), _("Add"), 'edit.png', $img_dir);

        if ($GLOBALS['conf']['attributes']['tags']) {
            $menu->add(Horde::applicationUrl('cloud.php'), _("Tag cloud"), 'colorpicker.png', $img_dir);
        }

        if ($GLOBALS['registry']->isAdmin(array('permission' => 'news:admin'))) {
            $menu->add(Horde::applicationUrl('edit.php'), _("Editorship"), 'config.png', $img_dir);
            $menu->add(Horde::applicationUrl('admin/categories/index.php'), _("Administration"), 'administration.png', $img_dir);
        }

        return $menu;
    }

}
