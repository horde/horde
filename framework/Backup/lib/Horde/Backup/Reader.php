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
use Horde\Backup\Translation;
use Horde\Backup\Users;

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
     * Returns user data from backups.
     */
    public function restore()
    {
    }
}
