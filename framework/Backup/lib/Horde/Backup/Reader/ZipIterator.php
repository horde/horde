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

namespace Horde\Backup\Reader;

use ArrayIterator;
use CallbackFilterIterator;
use IteratorIterator;
use Horde_Compress_Zip as Zip;
use Horde_Pack_Driver_Json as Json;

/**
 * Iterates over certain files in a ZIP archive.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Backup
 */
class ZipIterator extends IteratorIterator
{
    /**
     * The ZIP file contents.
     *
     * @var string
     */
    protected $_contents;

    /**
     * Archive info from Horde_Compress_Zip.
     *
     * @var array
     */
    protected $_info;

    /**
     * The ZIP uncompressor.
     *
     * @var Horde_Compress_Zip
     */
    protected $_compress;

    /**
     * The JSON unpacker.
     *
     * @var Horde_Pack_Driver_Json
     */
    protected $_packer;

    /**
     * Constructor.
     *
     * @param string $contents     The ZIP file contents.
     * @param string $application  An application name.
     * @param string $type         A collection type like "calendar" or
     *                             "contact".
     * @param array $info          ZIP archive info from Horde_Compress_Zip.
     */
    public function __construct($contents, $application, $type, $info)
    {
        $this->_contents = $contents;
        $this->_info = $info;
        $this->_packer = new Json();
        $this->_compress = new Zip();

        $iterator = new CallbackFilterIterator(
            new ArrayIterator($info),
            function ($current, $key, $iterator) use ($application, $type)
            {
                $path = explode('/', $current['name']);
                return $application == $path[0] && $type == $path[1];
            }
        );
        parent::__construct($iterator);
    }

    /**
     * Returns the unpacked backup data.
     *
     * @return \Horde\Backup\User
     */
    public function current()
    {
        return $this->_packer->unpack(
            $this->_compress->decompress(
                $this->_contents,
                array(
                    'action' => Zip::ZIP_DATA,
                    'info' => $this->_info,
                    'key' => $this->key()
                )
            )
        );
    }
}
