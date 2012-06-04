<?php
/**
 * Gollem base library.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class Gollem
{
    /* Sort constants. */
    const SORT_TYPE = 0;
    const SORT_NAME = 1;
    const SORT_DATE = 2;
    const SORT_SIZE = 3;

    const SORT_ASCEND = 0;
    const SORT_DESCEND = 1;

    /**
     * Configuration hash for the current backend.
     *
     * @var array
     */
    static public $backend;

    /**
     * Changes the current directory of the Gollem session to the supplied
     * value.
     *
     * @param string $dir  Directory name.
     *
     * @throws Gollem_Exception
     */
    static public function setDir($dir)
    {
        $dir = Horde_Util::realPath($dir);

        if (!self::verifyDir($dir) ||
            !self::checkPermissions('directory', Horde_Perms::READ, $dir)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        self::$backend['dir'] = $dir;

        self::_setLabel();
    }

    /**
     * Changes the current directory of the Gollem session based on the
     * 'dir' form field.
     *
     * @throws Gollem_Exception
     */
    static public function changeDir()
    {
        $dir = Horde_Util::getFormData('dir');
        if (is_null($dir)) {
            self::_setLabel();
        } else {
            if (strpos($dir, '/') !== 0) {
                $dir = self::$backend['dir'] . '/' . $dir;
            }
            self::setDir($dir);
        }
    }

    /**
     * Set the lable to use for the current page.
     */
    static protected function _setLabel()
    {
        self::$backend['label'] = self::getDisplayPath(self::$backend['dir']);
        if (empty(self::$backend['label'])) {
            self::$backend['label'] = '/';
        }
    }

    /**
     * Internal helper to sort directories first if pref set.
     */
    static protected function _sortDirs($a, $b)
    {
        /* Sort symlinks to dirs as dirs */
        $dira = ($a['type'] === '**dir') ||
            (($a['type'] === '**sym') && ($a['linktype'] === '**dir'));
        $dirb = ($b['type'] === '**dir') ||
            (($b['type'] === '**sym') && ($b['linktype'] === '**dir'));

        if ($GLOBALS['prefs']->getValue('sortdirsfirst')) {
            if ($dira && !$dirb) {
                return -1;
            } elseif (!$dira && $dirb) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * Internal sorting function for 'date'.
     */
    static public function sortDate($a, $b)
    {
        $dirs = self::_sortDirs($a, $b);
        if ($dirs) {
            return $dirs;
        }

        if ($a['date'] > $b['date']) {
            return $GLOBALS['prefs']->getValue('sortdir') ? -1 : 1;
        } elseif ($a['date'] === $b['date']) {
            return self::sortName($a, $b);
        } else {
            return $GLOBALS['prefs']->getValue('sortdir') ? 1 : -1;
        }
    }

    /**
     * Internal sorting function for 'size'.
     */
    static public function sortSize($a, $b)
    {
        $dirs = self::_sortDirs($a, $b);
        if ($dirs) {
            return $dirs;
        }

        if ($a['size'] > $b['size']) {
            return $GLOBALS['prefs']->getValue('sortdir') ? -1 : 1;
        } elseif ($a['size'] === $b['size']) {
            return 0;
        } else {
            return $GLOBALS['prefs']->getValue('sortdir') ? 1 : -1;
        }
    }

    /**
     * Internal sorting function for 'type'.
     */
    static public function sortType($a, $b)
    {
        $dirs = self::_sortDirs($a, $b);
        if ($dirs) {
            return $dirs;
        }

        if ($a['type'] === $b['type']) {
            return self::sortName($a, $b);
        } elseif ($a['type'] === '**dir') {
            return $GLOBALS['prefs']->getValue('sortdir') ? 1 : -1;
        } elseif ($b['type'] === '**dir') {
            return $GLOBALS['prefs']->getValue('sortdir') ? -1 : 1;
        } else {
            $res = strcasecmp($a['type'], $b['type']);
            return $GLOBALS['prefs']->getValue('sortdir') ? ($res * -1) : $res;
        }
    }

    /**
     * Internal sorting function for 'name'.
     */
    static public function sortName($a, $b)
    {
        $dirs = self::_sortDirs($a, $b);
        if ($dirs) {
            return $dirs;
        }

        $res = strcasecmp($a['name'], $b['name']);
        return $GLOBALS['prefs']->getValue('sortdir') ? ($res * -1) : $res;
    }

    /**
     * List the current folder.
     *
     * @param string $dir  The directory name.
     *
     * @return array  The sorted list of files.
     * @throws Gollem_Exception
     */
    static public function listFolder($dir)
    {
        global $conf;

        if (!empty($conf['foldercache']['use_cache']) &&
            !empty($conf['cache']['driver']) &&
            ($conf['cache']['driver'] != 'none')) {
            $key = self::_getCacheID($dir);

            $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
            $res = $cache->get($key, $conf['foldercache']['lifetime']);
            if ($res !== false) {
                $res = Horde_Serialize::unserialize($res, Horde_Serialize::BASIC);
                if (is_array($res)) {
                    return $res;
                }
            }
        }

        try {
            $files = $GLOBALS['injector']
                ->getInstance('Gollem_Vfs')
                ->listFolder($dir,
                             isset(self::$backend['filter']) ? self::$backend['filter'] : null,
                             $GLOBALS['prefs']->getValue('show_dotfiles'));
        } catch (Horde_Vfs_Exception $e) {
            throw new Gollem_Exception($e);
        }
        $sortcols = array(
            self::SORT_TYPE => 'sortType',
            self::SORT_NAME => 'sortName',
            self::SORT_DATE => 'sortDate',
            self::SORT_SIZE => 'sortSize',
        );
        usort($files, array('Gollem', $sortcols[$GLOBALS['prefs']->getValue('sortby')]));

        if (isset($cache)) {
            $cache->set($key, Horde_Serialize::serialize($files, Horde_Serialize::BASIC), $conf['foldercache']['lifetime']);
        }

        return $files;
    }

    /**
     * Generate the Cache ID for a directory.
     *
     * @param string $dir  The directory name.
     */
    static protected function _getCacheID($dir)
    {
        return implode('|', array($GLOBALS['registry']->getAuth(),
                                  $GLOBALS['session']->get('gollem', 'backend_key'),
                                  $GLOBALS['prefs']->getValue('show_dotfiles'),
                                  $GLOBALS['prefs']->getValue('sortdirsfirst'),
                                  $GLOBALS['prefs']->getValue('sortby'),
                                  $GLOBALS['prefs']->getValue('sortdir'),
                                  $dir));
    }

    /**
     * Expire a folder cache entry.
     *
     * @param string $dir  The directory name.
     */
    static public function expireCache($dir)
    {
        global $conf;

        if (!empty($conf['foldercache']['use_cache']) &&
            !empty($conf['cache']['driver']) &&
            ($conf['cache']['driver'] != 'none')) {
            $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
            $cache->expire(self::_getCacheID($dir));
        }
    }

    /**
     * Generate correct subdirectory links.
     *
     * @param string $base  The base directory.
     * @param string $dir   The directory string.
     *
     * @return string  The correct subdirectoy string.
     */
    static public function subdirectory($base, $dir)
    {
        if (empty($base)) {
            return $dir;
        }

        if (substr($base, -1) == '/') {
            return $base . $dir;
        }

        return $base . '/' . $dir;
    }

    /**
     * Create a folder using the current Gollem session settings.
     *
     * @param string $dir                 The directory path.
     * @param string $name                The folder to create.
     * @param Horde_Vfs_Base $gollem_vfs  A VFS instance to use.
     *
     * @throws Gollem_Exception
     * @throws Horde_Vfs_Exception
     */
    static public function createFolder($dir, $name, $gollem_vfs = null)
    {
        $totalpath = Horde_Util::realPath($dir . '/' . $name);
        if (!self::verifyDir($totalpath)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $totalpath));
        }

        /* The $name parameter may contain additional directories so we
         * need to pass autocreatePath everything but the base filename. */
        $pos = strrpos($totalpath, '/');
        $dir = substr($totalpath, 0, $pos);
        $name = substr($totalpath, $pos + 1);

        if (!$gollem_vfs) {
            $gollem_vfs = $GLOBALS['injector']->getInstance('Gollem_Vfs');
        }
        $gollem_vfs->autocreatePath($dir);
        $gollem_vfs->createFolder($dir, $name);

        if (!empty(self::$backend['params']['permissions'])) {
            $gollem_vfs->changePermissions($dir, $name, self::$backend['params']['permissions']);
        }
    }

    /**
     * Rename files using the current Gollem session settings.
     *
     * @param string $oldDir  Old directory name.
     * @param string $old     Old file name.
     * @param string $newDir  New directory name.
     * @param string $old     New file name.
     *
     * @throws Horde_Vfs_Exception
     */
    static public function renameItem($oldDir, $old, $newDir, $new)
    {
        $GLOBALS['injector']
            ->getInstance('Gollem_Vfs')
            ->rename($oldDir, $old, $newDir, $new);

        $shares = $GLOBALS['injector']->getInstance('Gollem_Shares');
        $backend = $GLOBALS['session']->get('gollem', 'backend_key');
        try {
            $share = $shares->getShare($backend . '|' . $oldDir . '/' . $old);
            $shares->renameShare($share, $backend . '|' . $newDir . '/' . $new);
            $share->set('name', $new, true);
        } catch (Horde_Exception_NotFound $e) {
        } catch (Horde_Share_Exception $e) {
            throw new Gollem_Exception($e);
        }
    }

    /**
     * Delete a folder using the current Gollem session settings.
     *
     * @param string $dir   The subdirectory name.
     * @param string $name  The folder name to delete.
     *
     * @throws Gollem_Exception
     * @throws Horde_Vfs_Exception
     */
    static public function deleteFolder($dir, $name)
    {
        if (!self::verifyDir($dir)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }

        $GLOBALS['injector']
            ->getInstance('Gollem_Vfs')
            ->deleteFolder($dir,
                           $name,
                           $GLOBALS['prefs']->getValue('recursive_deletes') != 'disabled');

        $shares = $GLOBALS['injector']->getInstance('Gollem_Shares');
        try {
            $share = $shares->getShare(
                $GLOBALS['session']->get('gollem', 'backend_key') . '|'
                . $dir . '/' . $name);
            $shares->removeShare($share);
        } catch (Horde_Exception_NotFound $e) {
        } catch (Horde_Share_Exception $e) {
            throw new Gollem_Exception($e);
        }
    }

    /**
     * Delete a file using the current Gollem session settings.
     *
     * @param string $dir   The directory name.
     * @param string $name  The filename to delete.
     *
     * @throws Gollem_Exception
     * @throws Horde_Vfs_Exception
     */
    static public function deleteFile($dir, $name)
    {
        if (!self::verifyDir($dir)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        $GLOBALS['injector']
            ->getInstance('Gollem_Vfs')
            ->deleteFile($dir, $name);
    }

    /**
     * Change permissions on files using the current Gollem session settings.
     *
     * @param string $dir         The directory name.
     * @param string $name        The filename to change permissions on.
     * @param string $permission  The permission mode to set.
     *
     * @throws Gollem_Exception
     * @throws Horde_Vfs_Exception
     */
    static public function changePermissions($dir, $name, $permission)
    {
        if (!self::verifyDir($dir)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        $GLOBALS['injector']
            ->getInstance('Gollem_Vfs')
            ->changePermissions($dir, $name, $permission);
    }

    /**
     * Write an uploaded file to the VFS backend.
     *
     * @param string $dir       The directory name.
     * @param string $name      The filename to create.
     * @param string $filename  The local file containing the file data.
     *
     * @thows Horde_Vfs_Exception
     */
    static public function writeFile($dir, $name, $filename)
    {
        $gollem_vfs = $GLOBALS['injector']->getInstance('Gollem_Vfs');
        $gollem_vfs->write($dir, $name, $filename, true);
        if (!empty(self::$backend['params']['permissions'])) {
            $gollem_vfs->changePermissions($dir, $name, self::$backend['params']['permissions']);
        }
    }

    /**
     * Moves a file using the current Gollem session settings.
     *
     * @param string $backend_f The backend to move the file from.
     * @param string $dir       The directory name of the original file.
     * @param string $name      The original filename.
     * @param string $backend_t The backend to move the file to.
     * @param string $newdir    The directory to move the file to.
     *
     * @throws Horde_Vfs_Exception
     */
    static public function moveFile($backend_f, $dir, $name, $backend_t,
                                    $newdir)
    {
        self::_copyFile('move', $backend_f, $dir, $name, $backend_t, $newdir);
    }

    /**
     * Copies a file using the current Gollem session settings.
     *
     * @param string $backend_f The backend to copy the file from.
     * @param string $dir       The directory name of the original file.
     * @param string $name      The original filename.
     * @param string $backend_t The backend to copy the file to.
     * @param string $newdir    The directory to copy the file to.
     *
     * @throws Horde_Vfs_Exception
     */
    static public function copyFile($backend_f, $dir, $name, $backend_t,
                                    $newdir)
    {
        self::_copyFile('copy', $backend_f, $dir, $name, $backend_t, $newdir);
    }

    /**
     * Private function that copies/moves files.
     *
     * @throws Horde_Vfs_Exception
     */
    static protected function _copyFile($mode, $backend_f, $dir, $name,
                                        $backend_t, $newdir)
    {
        $backend_key = $GLOBALS['session']->get('gollem', 'backend_key');

        /* If the from/to backends are the same, we can just use the built-in
         * VFS functions. */
        if ($backend_f == $backend_t) {
            $ob = $GLOBALS['injector']->getInstance('Gollem_Factory_Vfs')->create($backend_f);
            if ($backend_f != $backend_key) {
                $ob->checkCredentials();
            }
            return $mode == 'copy'
                ? $ob->copy($dir, $name, $newdir)
                : $ob->move($dir, $name, $newdir);
        }

        /* Else, get the two VFS objects and copy/move the files. */
        $from_be = $GLOBALS['injector']->getInstance('Gollem_Factory_Vfs')->create($backend_f);
        if ($backend_f != $backend_key) {
            $from_be->checkCredentials();
        }

        $to_be = $GLOBALS['injector']->getInstance('Gollem_Factory_Vfs')->create($backend_t);
        if ($backend_t != $backend_key) {
            $to_be->checkCredentials();
        }

        /* Read the source data. */
        $data = $from_be->read($dir, $name);

        /* Write the target data. */
        $to_be->writeData($newdir, $name, $data);

        /* If moving, delete the source data. */
        if ($mode == 'move') {
            $from_be->deleteFile($dir, $name);
        }
    }

    /**
     * This function verifies whether a given directory is below the root.
     *
     * @param string $dir  The directory to check.
     *
     * @return boolean  True if the directory is below the root.
     */
    static public function verifyDir($dir)
    {
        return Horde_String::substr(Horde_Util::realPath($dir), 0, Horde_String::length(self::$backend['root'])) == self::$backend['root'];
    }

    /**
     * Checks if a user has the specified permissions on a resource.
     *
     * @param string $filter       What are we checking for. Either 'backend'
     *                             or 'directory'.
     * @param integer $permission  The permission to check for. One of the
     *                             Horde_Perms constants.
     * @param string $resource     The resource to check. If empty, check the
     *                             current backend/directory.
     *
     * @return boolean  Returns true if the user has permission.
     */
    static public function checkPermissions($filter,
                                            $permission = Horde_Perms::READ,
                                            $resource = null)
    {
        $userID = $GLOBALS['registry']->getAuth();

        switch ($filter) {
        case 'backend':
            if (is_null($resource)) {
                $resource = $GLOBALS['session']->get('gollem', 'backend_key');
            }
            $backendTag = 'gollem:backends:' . $resource;
            $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
            return (!$perms->exists($backendTag) ||
                    $perms->hasPermission($backendTag, $userID, $permission));

        case 'directory':
            if (empty(self::$backend['shares'])) {
                return true;
            }
            if (is_null($resource)) {
                $resource = self::$backend['dir'];
            }
            if (strpos($resource, self::$backend['home']) === 0) {
                return true;
            }
            $shares = $GLOBALS['injector']->getInstance('Gollem_Shares');
            $backend = $GLOBALS['session']->get('gollem', 'backend_key');
            $directory = $resource;
            while (strlen($directory) && $directory != './' && $directory != '/') {
                try {
                    return $shares->getShare($backend . '|' . $directory)
                        ->hasPermission($userID, $permission);
                } catch (Horde_Exception_NotFound $e) {
                }
                $directory = dirname($directory);
            }
            /* Intermediate solution until we display shared folders
             * independent from the directory tree. Check if there are
             * any sub-directories with show permissions and allow
             * browsing the directory in this case. */
            if ($permission == Horde_Perms::READ ||
                $permission == Horde_Perms::SHOW) {
                $dirs = $shares->listShares($userID, array('perm' => Horde_Perms::SHOW));
                foreach ($dirs as $dir) {
                    if (strpos($dir->getName(), $backend . '|' . $resource) === 0) {
                        return true;
                    }
                }
            }
            break;
        }

        return false;
    }

    /**
     * Produces a directory link used for navigation.
     *
     * @param string $currdir  The current directory string.
     * @param string $url      The URL to link to.
     *
     * @return string  The directory navigation string.
     */
    static public function directoryNavLink($currdir, $url)
    {
        $label = array();
        $root_dir_name = self::$backend['name'];

        if ($currdir == $root_dir_name) {
            $label[] = '[' . $root_dir_name . ']';
        } else {
            $parts = explode('/', $currdir);
            $parts_count = count($parts);

            $url = new Horde_Url($url);
            $label[] = Horde::link($url->add('dir', self::$backend['root']), sprintf(_("Up to %s"), $root_dir_name)) . '[' . $root_dir_name . ']</a>';

            for ($i = 1; $i <= $parts_count; ++$i) {
                $part = array_slice($parts, 0, $i);
                $dir = implode('/', $part);
                if ((strstr($dir, self::$backend['root']) !== false) &&
                    (self::$backend['root'] != $dir)) {
                    if ($i == $parts_count) {
                        $label[] = $parts[($i - 1)];
                    } else {
                        $label[] = Horde::link($url->add('dir', $dir), sprintf(_("Up to %s"), $dir)) . $parts[($i - 1)] . '</a>';
                    }
                }
            }
        }

        return implode('/', $label);
    }

    /**
     * Build Gollem's menu.
     *
     * @return string  Menu.
     */
    static public function menu()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');

        $t->set('login_url', $GLOBALS['registry']->getServiceLink('login', 'horde'));
        $t->set('forminput', Horde_Util::formInput());
        $t->set('hidden', array(array('key' => 'url', 'value' => (string)Horde::url('manager.php', true)),
                                array('key' => 'app', 'value' => 'gollem')));
        $t->set('be_select', self::backendSelect(), true);
        if ($t->get('be_select')) {
            $t->set('accesskey', $GLOBALS['prefs']->getValue('widget_accesskey') ? Horde::getAccessKey(_("_Change Server")) : '');
            $menu_view = $GLOBALS['prefs']->getValue('menu_view');
            $link = Horde::link('#', _("Change Server"), '', '', 'serverSubmit(true);return false;');
            $t->set('slink', sprintf('<ul><li>%s%s<br />%s</a></li></ul>', $link, ($menu_view != 'text') ? Horde::img('gollem.png') : '', ($menu_view != 'icon') ? Horde::highlightAccessKey(_("_Change Server"), $t->get('accesskey')) : ''));
        }
        $t->set('menu_string', Horde::menu(array('menu_ob' => true))->render());

        $menu = $t->fetch(GOLLEM_TEMPLATES . '/menu.html');

        return $GLOBALS['injector']
            ->getInstance('Horde_View_Topbar')
            ->render()
            . $menu;
    }

    /**
     * Outputs Gollem's status/notification bar.
     */
    static public function status()
    {
        $GLOBALS['notification']->notify(array('listeners' => 'status'));
    }

    /**
     * Generate the backend selection list for use in the menu.
     *
     * @return string  The backend selection list.
     */
    static public function backendSelect()
    {
        $backends = Gollem_Auth::getBackend();
        $backend = Gollem_Auth::getPreferredBackend();
        $text = '';

        if ($GLOBALS['conf']['backend']['backend_list'] == 'shown' &&
            count($backends) > 1) {
            foreach ($backends as $key => $val) {
                $sel = ($backend == $key) ? ' selected="selected"' : '';
                $text .= sprintf('<option value="%s"%s>%s</option>%s',
                                 empty($sel) ? $key : '',
                                 $sel,
                                 $val['name'],
                                 "\n");
            }
        }

        return $text;
    }

    /**
     * Generate the display path (the path with any root information stripped
     * out).
     *
     * @param string $path  The path to display.
     *
     * @return string  The display path.
     */
    static public function getDisplayPath($path)
    {
        $path = Horde_Util::realPath($path);
        if (self::$backend['root'] != '/' &&
            strpos($path, self::$backend['root']) === 0) {
            $path = substr($path, Horde_String::length(self::$backend['root']));
        }
        return $path;
    }

    /**
     * Cleans a path presented to Gollem's browse API call.
     *
     * This will remove:
     * - leading '/'
     * - leading 'gollem'
     * - trailing '/'
     * The desired end result is the path including VFS backend.
     *
     * @param string $path  Path as presented to Gollem API.
     *
     * @return string  Cleaned path as described above.
     */
    static public function stripAPIPath($path)
    {
        // Strip leading '/'
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        // Remove 'gollem' from path
        if (substr($path, 0, 6) == 'gollem') {
            $path = substr($path, 6);
        }
        // Remove leading '/'
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        // Remove trailing '/'
        if (substr($path, -1) == '/') {
            $path = substr($path, 0, -1);
        }
        return $path;
    }

    /**
     * Convert a Gollem path into a URL encoded string, but keep '/'.
     * This allows for proper PATH_INFO path parsing.
     * Special care is taken to handle "+" and " ".
     *
     * @param string $path  Path to be urlencode()d.
     *
     * @return string  URL-encoded string with '/' preserved.
     */
    static public function pathEncode($path)
    {
        return str_ireplace(array('%2F', '%2f'), '/', rawurlencode($path));
    }

    /**
     * Take a fully qualified and break off the file or directory name.
     * This pair is used for the input to many VFS library functions.
     *
     * @param string $fullpath   Path to be split.
     *
     * @return array  Array of ($path, $name)
     */
    static public function getVFSPath($fullpath)
    {
        // Convert the path into VFS's ($path, $name) convention
        $i = strrpos($fullpath, '/');
        if ($i !== false) {
            $path = substr($fullpath, 0, $i);
            $name = substr($fullpath, $i + 1);
        } else {
            $name = $fullpath;
            $path = '';
        }
        return array($name, $path);
    }
}
