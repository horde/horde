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

use Horde_Compress_Zip as Compress;
use Horde\Backup\Reader\CompressIterator;

/**
 * Iterates over certain files in a ZIP archive.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Backup
 */
class Zip extends CompressIterator
{
    /**
     * The ZIP file contents.
     *
     * @var string
     */
    protected $_contents;

    /**
     * The ZIP uncompressor.
     *
     * @var Horde_Compress_Zip
     */
    protected $_compress;

    /**
     * Constructor.
     *
     * @param string $application  An application name.
     * @param string $type         A collection type like "calendar" or
     *                             "contact".
     * @param array $info          ZIP archive info from Horde_Compress_Zip.
     * @param string $contents     The ZIP file contents.
     */
    public function __construct($application, $type, $info, $contents)
    {
        $this->_contents = $contents;
        $this->_compress = new Compress();
        parent::__construct($application, $type, $info);
    }

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
        return $this->_packer->unpack(
            $this->_compress->decompress(
                $this->_contents,
                array(
                    'action' => Compress::ZIP_DATA,
                    'info' => $this->_info,
                    'key' => parent::key()
                )
            )
        );
    }
}
