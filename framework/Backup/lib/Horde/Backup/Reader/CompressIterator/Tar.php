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

namespace Horde\Backup\Reader\CompressIterator;

use Horde\Backup\Reader\CompressIterator;

/**
 * Iterates over certain files in a TAR archive.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Backup
 */
class Tar extends CompressIterator
{
    /**
     * Returns the object ID.
     *
     * @return string  Object ID.
     */
    public function key()
    {
        return basename($this->_info[parent::key()]['name']);
    }

    /**
     * Returns the unpacked backup data.
     *
     * @return mixed  Backup data.
     */
    public function current()
    {
        return $this->_packer->unpack($this->_info[parent::key()]['data']);
    }
}
