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
 * Iterates over certain files from a Horde_Compress archive.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Backup
 */
abstract class CompressIterator extends IteratorIterator
{
    /**
     * Archive info from Horde_Compress_Zip.
     *
     * @var array
     */
    protected $_info;

    /**
     * The JSON unpacker.
     *
     * @var Horde_Pack_Driver_Json
     */
    protected $_packer;

    /**
     * Constructor.
     *
     * @param string $application  An application name.
     * @param string $type         A collection type like "calendar" or
     *                             "contact".
     * @param array $info          ZIP archive info from Horde_Compress_Zip.
     */
    public function __construct($application, $type, $info)
    {
        $this->_info = $info;
        $this->_packer = new Json();

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
     * @return mixed  Backup data.
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
