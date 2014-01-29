<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress_Fast
 */

/**
 * Provides fast compression of strings using the best-available algorithm.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress_Fast
 *
 * @property string $driver  Returns the name of the compression driver used.
 */
class Horde_Compress_Fast
{
    /**
     * Compression driver
     *
     * @var Horde_Compress_Fast_Base
     */
    protected $_compress;

    /**
     * Constructor.
     *
     * @param array $opts  Options:
     *   - drivers: (array) A list of driver names (Horde_Compress_Fast_Base
     *              class names) to use instead of auto-detecting.
     *
     * @throws Horde_Compress_Fast_Exception
     */
    public function __construct(array $opts = array())
    {
        if (empty($opts['drivers'])) {
            if (Horde_Compress_Fast_Lz4::supported()) {
                $this->_compress = new Horde_Compress_Fast_Lz4();
            } else {
                $this->_compress = Horde_Compress_Fast_Lzf::supported()
                    ? new Horde_Compress_Fast_Lzf()
                    : new Horde_Compress_Fast_Null();
            }
        } else {
            foreach ($opts['drivers'] as $val) {
                if (($ob = new $val()) &&
                    ($ob instanceof Horde_Compress_Fast_Base) &&
                    $val::supported()) {
                    $this->_compress = $ob;
                    break;
                }
            }

            if (!isset($this->_compress)) {
                throw new Horde_Compress_Fast_Exception('Could not load a valid compression driver.');
            }
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'driver':
            return get_class($this->_compress);
        }
    }

    /**
     * Compresses a string.
     *
     * @param string $text  The string to compress.
     *
     * @return string  The compressed string.
     * @throws Horde_Compress_Fast_Exception
     */
    public function compress($text)
    {
        if (!is_string($text)) {
            throw new Horde_Compress_Fast_Exception('Data to compress must be a string.');
        }

        return $this->_compress->compress($text);
    }

    /**
     * Decompresses a string.
     *
     * @param string $text  The compressed string.
     *
     * @return string  The decompressed string.
     * @throws Horde_Compress_Fast_Exception
     */
    public function decompress($text)
    {
        if (!is_string($text)) {
            throw new Horde_Compress_Fast_Exception('Data to decompress must be a string.');
        }

        return $this->_compress->decompress($text);
    }

}
