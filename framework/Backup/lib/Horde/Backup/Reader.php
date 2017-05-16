<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Backup
 */

namespace Horde\Backup;

use Horde_Compress_Zip as Zip;
use Horde_Pack_Driver_Json as Json;
use Horde\Backup\Exception;
use Horde\Backup\Reader\ZipIterator;
use Horde\Backup\Translation;

/**
 * The backup reader class that reads backups from the backup directory.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Backup
 */
class Reader
{
    /**
     * Backup directory.
     *
     * @var string
     */
    protected $_dir;

    /**
     * Constructor.
     *
     * @param string $directory  Backup directory.
     */
    public function __construct($directory)
    {
        if (!Json::supported()) {
            throw new Exception(Translation::t("JSON serializer not available"));
        }

        $this->_dir = $directory;
        if (!strlen($this->_dir)) {
            throw new Exception(Translation::t("Empty directory name"));
        }
        if (!is_dir($this->_dir)) {
            throw new Exception(
                sprintf(Translation::t("%s is not a directory"), $this->_dir)
            );
        }
        if (!is_readable($this->_dir) || !is_writable($this->_dir)) {
            throw new Exception(
                sprintf(Translation::t("Access denied to %s"), $this->_dir)
            );
        }
    }

    /**
     * Returns the available user backups.
     *
     * @return Iterator  A list of user backups.
     */
    public function listBackups()
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->_dir,
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            )
        );
    }

    /**
     * Returns user data from backups.
     *
     * @param array $users         A list of users to restore. Defaults to all
     *                             backups.
     * @param array $applications  A list of applications to restore. Defaults
     *                             to all backups.
     */
    public function restore(
        array $users = array(), $applications = array()
    )
    {
        if ($users) {
            $backups = new ArrayIterator($this->_getBackupFiles($users));
        } else {
            $backups = $this->listBackups();
        }

        $data = array();
        foreach ($backups as $file) {
            switch (substr($file, -4)) {
            case '.zip':
                $data = array_merge_recursive(
                    $data,
                    $this->_restoreFromZip($file, $applications)
                );
                break;
            case '.tar':
                $data = array_merge_recursive(
                    $data,
                    $this->_restoreFromTar($file, $applications)
                );
                break;
            }
        }

        return $data;
    }

    /**
     * Restores user data from a ZIP file.
     *
     * @param string $file         Pathname to a ZIP backup file.
     * @param array $applications  A list of applications to restore. Defaults
     *                             to all backups.
     *
     * @return \Horde\Backup\Collection[]  All restored object collections.
     */
    protected function _restoreFromZip($file, $applications)
    {
        $user = basename($file, '.zip');
        $contents = file_get_contents($file);
        $packer = new Json();
        $compress = new Zip();
        $files = $compress->decompress(
            $contents, array('action' => Zip::ZIP_LIST)
        );

        $data = array();
        foreach ($files as $key => $info) {
            $path = explode('/', $info['name']);
            if (!$applications || in_array($path[0], $applications)) {
                $data[$path[0]][$path[1]] = true;
            }
        }

        $collections = array();
        foreach ($data as $application => $resources) {
            $collections[$application] = array();
            foreach (array_keys($resources) as $resource) {
                $collections[$application][] = new Collection(
                    new ZipIterator($contents, $application, $resource, $files),
                    $user,
                    $resource
                );
            }
        }

        return $collections;
    }


    /**
     * Builds a backup iterator for individual users.
     *
     * @param array $users  A list of users.
     *
     * @return array  A list of backup files.
     */
    protected function _getBackupFiles(array $users)
    {
        $files = array();
        foreach ($users as $user) {
            $file = $this->_temp . '/' . $user;
            if (file_exists($file . '.zip')) {
                $files[] = $file . '.zip';
            } elseif (file_exists($file . '.tar')) {
                $files[] = $file . '.tar';
            }
        }
        return $files;
    }
}
