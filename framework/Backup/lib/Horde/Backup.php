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

namespace Horde;

use Horde_Compress_Zip as Zip;
use Horde_Pack_Driver_Json as Json;
use Horde\Backup\Exception;
use Horde\Backup\Translation;
use Horde\Backup\Users;

/**
 * A collection of backup objects of a certain application-specific type.
 *
 * Each application should extend this class for each type of backup data it
 * supports.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Backup
 */
class Backup
{
    /**
     * Create ZIP archives.
     */
    const FORMAT_ZIP = 1;

    /**
     * Create TAR archives.
     */
    const FORMAT_TAR = 2;

    /**
     * Backup directory.
     *
     * @var string
     */
    protected $_dir;

    /**
     * Handles for backup data.
     *
     * @var array
     */
    protected $_backups = array();

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
        if (!file_exists($this->_dir)) {
            mkdir($this->_dir, 0777, true);
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
     * Adds backups of user data.
     *
     * @param string $application         Application name.
     * @param \Horde\Backup\Users $users  User(s) (and their data) to backup.
     */
    public function backup($application, Users $users)
    {
        $this->_backups[$application] = $users;
    }

    /**
     * Saves the backups.
     */
    public function save($format = self::FORMAT_ZIP)
    {
        $backups = array();
        foreach ($this->_backups as $application => $users) {
            foreach ($users as $user) {
                $backups[$user->user][$application] = $user;
            }
        }

        switch ($format) {
        case self::FORMAT_ZIP:
            $compress = new Zip();
            $extension = '.zip';
            break;
        case self::FORMAT_TAR:
            $compress = new Tar();
            $extension = '.tar';
            break;
        default:
            throw new Exception(Translation::t("Unsupported archive type"));
        }
        $packer = new Json();

        foreach ($backups as $name => $applications) {
            $data = array();
            foreach ($applications as $application => $backup) {
                foreach ($backup->collections as $collection) {
                    $dir = $application . '/' . $collection->getType() . '/';
                    foreach ($collection as $id => $object) {
                        $stream = fopen('php://temp', 'w+');
                        fwrite($stream, $packer->pack($object));
                        $data[] = array(
                            'name' => $dir . $id,
                            'data' => $stream
                        );
                    }
                }
            }
            $archive = fopen($this->_dir . '/' . $name . $extension, 'w');
            stream_copy_to_stream(
                $compress->compress($data, array('stream' => true)),
                $archive
            );
            fclose($archive);
        }
    }

    /**
     * Returns user data from backups.
     */
    public function restore()
    {
    }
}