<?php
/**
 * Gollem base library.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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

        if (!self::verifyDir($dir)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        $GLOBALS['gollem_be']['dir'] = $dir;

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
                $dir = $GLOBALS['gollem_be']['dir'] . '/' . $dir;
            }
            self::setDir($dir);
        }
    }

    /**
     * Get the root directory of the Gollem session.
     *
     * @return string  The root directory.
     */
    static public function getRoot()
    {
        return $GLOBALS['gollem_be']['root'];
    }

    /**
     * Get the home directory of the Gollem session.
     *
     * @return string  The home directory.
     */
    static public function getHome()
    {
        return $GLOBALS['gollem_be']['home'];
    }

    /**
     * Get the current directory of the Gollem session.
     *
     * @return mixed  Current dir on success or null on failure.
     */
    static public function getDir()
    {
        return $GLOBALS['gollem_be']['dir'];
    }

    /**
     * Set the lable to use for the current page.
     */
    static protected function _setLabel()
    {
        $GLOBALS['gollem_be']['label'] = self::getDisplayPath($GLOBALS['gollem_be']['dir']);
        if (empty($GLOBALS['gollem_be']['label'])) {
            $GLOBALS['gollem_be']['label'] = '/';
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
     * @throws VFS_Exception
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

        $files = $GLOBALS['gollem_vfs']->listFolder($dir, isset($GLOBALS['gollem_be']['filter']) ? $GLOBALS['gollem_be']['filter'] : null, $GLOBALS['prefs']->getValue('show_dotfiles'));
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
        global $prefs;
        return implode('|', array($GLOBALS['registry']->getAuth(), $_SESSION['gollem']['backend_key'], $prefs->getValue('show_dotfiles'), $prefs->getValue('sortdirsfirst'), $prefs->getValue('sortby'), $prefs->getValue('sortdir'), $dir));
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
     * Generate an URL to the logout screen that includes any known
     * information, such as username, server, etc., that can be filled
     * in on the login form.
     *
     * @return Horde_Url  The logout URL with parameters added.
     */
    static public function logoutUrl()
    {
        $url = Horde::url('login.php', true);

        if (!empty($GLOBALS['gollem_be']['params']['username'])) {
            $url->add('username', $GLOBALS['gollem_be']['params']['username']);
        } elseif (Horde_Util::getFormData('username')) {
            $url->add('username', Horde_Util::getFormData('username'));
        }

        if (!empty($GLOBALS['gollem_be']['params']['port'])) {
            $url->add('port', $GLOBALS['gollem_be']['params']['port']);
        }

        return $url;
    }

    /**
     * Create a folder using the current Gollem session settings.
     *
     * @param string $dir   The directory path.
     * @param string $name  The folder to create.
     *
     * @throws Gollem_Exception
     * @throws VFS_Exception
     */
    static public function createFolder($dir, $name)
    {
        $totalpath = Horde_Util::realPath($dir . '/' . $name);
        if (!Gollem::verifyDir($totalpath)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $totalpath));
        }

        /* The $name parameter may contain additional directories so we
         * need to pass autocreatePath everything but the base filename. */
        $pos = strrpos($totalpath, '/');
        $dir = substr($totalpath, 0, $pos);
        $name = substr($totalpath, $pos + 1);

        $GLOBALS['gollem_vfs']->autocreatePath($dir);
        $GLOBALS['gollem_vfs']->createFolder($dir, $name);

        if (!empty($GLOBALS['gollem_be']['params']['permissions'])) {
            $GLOBALS['gollem_vfs']->changePermissions($dir, $name, $GLOBALS['gollem_be']['params']['permissions']);
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
     * @throws VFS_Exception
     */
    static public function renameItem($oldDir, $old, $newDir, $new)
    {
        $GLOBALS['gollem_vfs']->rename($oldDir, $old, $newDir, $new);
    }

    /**
     * Delete a folder using the current Gollem session settings.
     *
     * @param string $dir   The subdirectory name.
     * @param string $name  The folder name to delete.
     *
     * @throws Gollem_Exception
     * @throws VFS_Exception
     */
    static public function deleteFolder($dir, $name)
    {
        if (!Gollem::verifyDir($dir)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }

        ($GLOBALS['prefs']->getValue('recursive_deletes') != 'disabled')
            ? $GLOBALS['gollem_vfs']->deleteFolder($dir, $name, true)
            : $GLOBALS['gollem_vfs']->deleteFolder($dir, $name, false);
    }

    /**
     * Delete a file using the current Gollem session settings.
     *
     * @param string $dir   The directory name.
     * @param string $name  The filename to delete.
     *
     * @throws Gollem_Exception
     * @throws VFS_Exception
     */
    static public function deleteFile($dir, $name)
    {
        if (!Gollem::verifyDir($dir)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        $GLOBALS['gollem_vfs']->deleteFile($dir, $name);
    }

    /**
     * Change permissions on files using the current Gollem session settings.
     *
     * @param string $dir         The directory name.
     * @param string $name        The filename to change permissions on.
     * @param string $permission  The permission mode to set.
     *
     * @throws Gollem_Exception
     * @throws VFS_Exception
     */
    static public function changePermissions($dir, $name, $permission)
    {
        if (!Gollem::verifyDir($dir)) {
            throw new Gollem_Exception(sprintf(_("Access denied to folder \"%s\"."), $dir));
        }
        $GLOBALS['gollem_vfs']->changePermissions($dir, $name, $permission);
    }

    /**
     * Write an uploaded file to the VFS backend.
     *
     * @param string $dir       The directory name.
     * @param string $name      The filename to create.
     * @param string $filename  The local file containing the file data.
     *
     * @thows VFS_Exception
     */
    static public function writeFile($dir, $name, $filename)
    {
        $GLOBALS['gollem_vfs']->write($dir, $name, $filename);
        if (!empty($GLOBALS['gollem_be']['params']['permissions'])) {
            $GLOBALS['gollem_vfs']->changePermissions($dir, $name, $GLOBALS['gollem_be']['params']['permissions']);
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
     * @throws VFS_Exception
     */
    static public function moveFile($backend_f, $dir, $name, $backend_t,
                                    $newdir)
    {
        Gollem::_copyFile('move', $backend_f, $dir, $name, $backend_t, $newdir);
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
     * @throws VFS_Exception
     */
    static public function copyFile($backend_f, $dir, $name, $backend_t,
                                    $newdir)
    {
        Gollem::_copyFile('copy', $backend_f, $dir, $name, $backend_t, $newdir);
    }

    /**
     * Private function that copies/moves files.
     *
     * @throws VFS_Exception
     */
    static protected function _copyFile($mode, $backend_f, $dir, $name,
                                        $backend_t, $newdir)
    {
        /* If the from/to backends are the same, we can just use the built-in
           VFS functions. */
        if ($backend_f == $backend_t) {
            if ($backend_f == $_SESSION['gollem']['backend_key']) {
                $ob = &$GLOBALS['gollem_vfs'];
            } else {
                $ob = Gollem::getVFSOb($backend_f);
                $ob->checkCredentials();
            }
            return ($mode == 'copy') ? $ob->copy($dir, $name, $newdir) : $ob->move($dir, $name, $newdir);
        }

        /* Else, get the two VFS objects and copy/move the files. */
        if ($backend_f == $_SESSION['gollem']['backend_key']) {
            $from_be = &$GLOBALS['gollem_vfs'];
        } else {
            $from_be = Gollem::getVFSOb($backend_f);
            $from_be->checkCredentials();
        }

        if ($backend_t == $_SESSION['gollem']['backend_key']) {
            $to_be = &$GLOBALS['gollem_vfs'];
        } else {
            $from_be = Gollem::getVFSOb($backend_t);
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
     * Get the current preferred backend key.
     *
     * @return string  The preferred backend key.
     */
    static public function getPreferredBackend()
    {
        $backend_key = null;

        if (!empty($_SESSION['gollem']['backend_key'])) {
            $backend_key = $_SESSION['gollem']['backend_key'];
        } else {
            /* Determine the preferred backend. */
            foreach ($GLOBALS['gollem_backends'] as $key => $val) {
                if (empty($backend_key) && (substr($key, 0, 1) != '_')) {
                    $backend_key = $key;
                }
                if (!empty($val['preferred'])) {
                    $preferred = false;
                    if (!is_array($val['preferred'])) {
                        $val['preferred'] = array($val['preferred']);
                    }
                    foreach ($val['preferred'] as $backend) {
                        if (($backend == $_SERVER['SERVER_NAME']) ||
                            ($backend == $_SERVER['HTTP_HOST'])) {
                            $preferred = true;
                            break;
                        }
                    }
                    if ($preferred) {
                        $backend_key = $key;
                        break;
                    }
                }
            }

        }

        return $backend_key;
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
        $rootdir = Gollem::getRoot();
        return (Horde_String::substr(Horde_Util::realPath($dir), 0, Horde_String::length($rootdir)) == $rootdir);
    }

    /**
     * Checks if a user has the specified permissions on the selected backend.
     *
     * @param string $filter       What are we checking for.
     * @param integer $permission  What permission to check for.
     * @param string $backend      The backend to check.  If empty, check
     *                             the current backend.
     *
     * @return boolean  Returns true if the user has permission, false if
     *                  they do not.
     */
    static public function checkPermissions($filter,
                                            $permission = Horde_Perms::READ,
                                            $backend = null)
    {
        $userID = $GLOBALS['registry']->getAuth();
        if (is_null($backend)) {
            $backend = $_SESSION['gollem']['backend_key'];
        }

        switch ($filter) {
        case 'backend':
            $backendTag = 'gollem:backends:' . $backend;
            return (!$GLOBALS['injector']->getInstance('Horde_Perms')->exists($backendTag) ||
                    $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission($backendTag, $userID, $permission));
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
        $root_dir = Gollem::getRoot();
        $root_dir_name = $_SESSION['gollem']['backends'][$_SESSION['gollem']['backend_key']]['name'];

        if ($currdir == $root_dir) {
            $label[] = '[' . $root_dir_name . ']';
        } else {
            $parts = explode('/', $currdir);
            $parts_count = count($parts);

            $url = new Horde_Url($url);
            $label[] = Horde::link($url->add('dir', $root_dir), sprintf(_("Up to %s"), $root_dir_name)) . '[' . $root_dir_name . ']</a>';

            for ($i = 1; $i <= $parts_count; ++$i) {
                $part = array_slice($parts, 0, $i);
                $dir = implode('/', $part);
                if ((strstr($dir, $root_dir) !== false) &&
                    ($root_dir != $dir)) {
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

        $t->set('forminput', Horde_Util::formInput());
        $t->set('be_select', self::backendSelect(), true);
        if ($t->get('be_select')) {
        $t->set('accesskey', $GLOBALS['prefs']->getValue('widget_accesskey') ? Horde::getAccessKey(_("_Change Server")) : '');
            $menu_view = $GLOBALS['prefs']->getValue('menu_view');
            $link = Horde::link('#', _("Change Server"), '', '', 'serverSubmit(true);return false;');
            $t->set('slink', sprintf('<ul><li>%s%s<br />%s</a></li></ul>', $link, ($menu_view != 'text') ? Horde::img('gollem.png') : '', ($menu_view != 'icon') ? Horde::highlightAccessKey(_("_Change Server"), $t->get('accesskey')) : ''));
        }
        $t->set('menu_string', Horde::menu(array('menu_ob' => true))->render());

        $menu = $t->fetch(GOLLEM_TEMPLATES . '/menu/menu.html');

        /* Need to buffer sidebar output here, because it may add things like
         * cookies which need to be sent before output begins. */
        Horde::startBuffer();
        require HORDE_BASE . '/services/sidebar.php';
        return $menu .= Horde::endBuffer();
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
        $text = '';

        if (($GLOBALS['conf']['backend']['backend_list'] == 'shown') &&
            (count($GLOBALS['gollem_backends']) > 1)) {
            foreach ($GLOBALS['gollem_backends'] as $key => $val) {
                $sel = ($_SESSION['gollem']['backend_key'] == $key) ? ' selected="selected"' : '';
                $text .= sprintf('<option value="%s"%s>%s</option>%s', (empty($sel)) ? $key : '', $sel, $val['name'], "\n");
            }
        }

        return $text;
    }

    /**
     * Load the backends list into the global $gollem_backends variable.
     */
    static public function loadBackendList()
    {
        if (!empty($_SESSION['gollem']['be_list'])) {
            $GLOBALS['gollem_backends'] = $_SESSION['gollem']['be_list'];
        } else {
            require GOLLEM_BASE . '/config/backends.php';
            $GLOBALS['gollem_backends'] = array();
            foreach ($backends as $key => $val) {
                if (Gollem::checkPermissions('backend', Horde_Perms::SHOW, $key)) {
                    $GLOBALS['gollem_backends'][$key] = $val;
                }
            }
        }
    }

    /**
     * Get the authentication ID to use for autologins based on the value of
     * the 'hordeauth' parameter.
     *
     * @param string $backend  The backend to login to.
     *
     * @return string  The ID string to use for logins.
     */
    static public function getAutologinID($backend)
    {
        return (!empty($GLOBALS['gollem_backends'][$backend]['hordeauth']) &&
                (strcasecmp($GLOBALS['gollem_backends'][$backend]['hordeauth'], 'full') === 0))
            ? $GLOBALS['registry']->getAuth()
            : $GLOBALS['registry']->getAuth('bare');
    }

    /**
     * Return a Horde_VFS object for the given backend.
     *
     * @param string $backend_key  The backend_key VFS object to return.
     *
     * @return object  The Horde_VFS object requested.
     */
    function getVFSOb($backend_key, $params = array())
    {
        if (isset($_SESSION['gollem']['backends'][$backend_key])) {
            $be_config = &$_SESSION['gollem']['backends'][$backend_key];
        } else {
            $be_config = $GLOBALS['gollem_backends'][$backend_key];
        }
        if (!count($params)) {
            $params = $be_config['params'];
            if (!empty($params['password'])) {
                $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
                $params['password'] = $secret->read($secret->getKey('gollem'), $params['password']);
            }
        }

        // Create VFS object
        $ob = VFS::singleton($be_config['driver'], $params);

        // Enable logging within VFS
        $ob->setLogger($GLOBALS['injector']->getInstance('Horde_Log_Logger'));

        if (!isset($be_config['quota_val']) &&
            !empty($be_config['quota'])) {
            $quota_metric = array(
                'B' => VFS_QUOTA_METRIC_BYTE,
                'KB' => VFS_QUOTA_METRIC_KB,
                'MB' => VFS_QUOTA_METRIC_MB,
                'GB' => VFS_QUOTA_METRIC_GB
            );
            $quota_str = explode(' ', $be_config['quota'], 2);
            if (is_numeric($quota_str[0])) {
                $metric = trim(strtoupper($quota_str[1]));
                if (!isset($quota_metric[$metric])) {
                    $metric = 'B';
                }
                $ob->setQuota($quota_str[0], $quota_metric[$metric]);
                $ob->setQuotaRoot(Gollem::getRoot());
                if ($sess_setup) {
                    $be_config['quota_val'] = $quota_str[0];
                    $be_config['quota_metric'] = $quota_metric[$metric];
                }
            }
        } elseif ($be_config['quota_val'] > -1) {
            $ob->setQuota($be_config['quota_val'], $be_config['quota_metric']);
            $ob->setQuotaRoot($be_config['root']);
        }

        return $ob;
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
        $rootdir = Gollem::getRoot();
        if (($rootdir != '/') && (strpos($path, $rootdir) === 0)) {
            $path = substr($path, Horde_String::length($rootdir));
        }
        return $path;
    }


    /**
     * Get a list of the available backends for permissions setup.
     *
     * @param string $perms  'all' - Return all backends.
     *                       'perms' - Return backends which have perms set.
     *                       'noperms' - Return backends which have no perms
     *                                   set.
     *
     * @return array  The requested backend list.
     */
    static public function getBackends($perms = 'all')
    {
        $backends = $_SESSION['gollem']['backends'];
        $perms = strtolower($perms);

        if ($perms != 'all') {
            foreach (array_keys($backends) as $key) {
                $exists = $GLOBALS['injector']->getInstance('Horde_Perms')->exists('gollem:backends:' . $key);
                /* Don't list if the perms don't exist for this backend and we
                 * want backends with perms only OR if the perms exist for
                 * this backend and we only want backends which have none. */
                if ((!$exists && ($perms == 'perms')) ||
                    ($exists && ($perms == 'noperms'))) {
                    unset($backends[$key]);
                }
            }
        }

        return $backends;
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
