<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */

/**
 * A replacement for serialize()/json_encode() that will automatically
 * use the most efficient serialization available based on the input.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */
class Horde_Pack
{
    /* Default compress length (in bytes). */
    const DEFAULT_COMPRESS = 128;

    /* Mask for compressed data. */
    const COMPRESS_MASK = 64;

    /**
     * Instance of Horde_Compress_Fast shared between all instances.
     *
     * @var Horde_Compress_Fast
     */
    static protected $_compress;

    /**
     * Drivers. Shared between all instances.
     *
     * @var array
     */
    static protected $_drivers = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (empty(self::$_drivers)) {
            $fi = new FilesystemIterator(__DIR__ . '/Pack/Driver');
            $class_prefix = __CLASS__ . '_Driver_';

            foreach ($fi as $val) {
                if ($val->isFile()) {
                    $cname = $class_prefix . $val->getBasename('.php');
                    if (class_exists($cname) && $cname::supported()) {
                        $ob = new $cname();
                        self::$_drivers[$ob->id] = $ob;
                    }
                }
            }

            krsort(self::$_drivers, SORT_NUMERIC);

            self::$_compress = new Horde_Compress_Fast();
        }
    }

    /**
     * Pack a string.
     *
     * @param mixed $data     The data to pack.
     * @param array $opts     Additional options:
     * <pre>
     *   - compress: (mixed) If false, don't use compression. If true, uses
     *               default compress length (DEFAULT). If 0, always compress.
     *               All other integer values: compress only if data is
     *               greater than this string length.
     *   - drivers: (array) Only use these drivers to pack. By default, driver
     *              to use is auto-determined.
     *   - phpob: (boolean) If true, the data contains PHP serializable
     *            objects (i.e. objects that have a PHP-specific serialized
     *            representation). If false, the data does not contain any of
     *            these objects. If not present, will auto-determine
     *            existence of these objects.
     * </pre>
     *
     * @return string  The packed string.
     * @throws Horde_Pack_Exception
     */
    public function pack($data, array $opts = array())
    {
        $opts = array_merge(array(
            'compress' => true
        ), $opts);

        if (!isset($opts['phpob'])) {
            $auto = new Horde_Pack_Autodetermine($data);
            $opts['phpob'] = $auto->phpob;
        }

        foreach (self::$_drivers as $key => $val) {
            if (!empty($opts['phpob']) && !$val->phpob) {
                continue;
            }

            if (isset($opts['drivers'])) {
                if (!in_array(get_class($val), $opts['drivers'])) {
                    continue;
                }
            }

            /* Format of data:
             * First-byte:
             *   1,2,4,8,16,32 - Reserved for pack format.
             *   64 - Packed data has been compressed.
             *   128 - RESERVED for future use (if set, indicates that initial
             *         byte will extend into next byte).
             * Packed (and compressed data) follows this byte. */
            $packed = $val->pack($data);

            if ($opts['compress'] !== false) {
                if ($opts['compress'] === 0) {
                    $compress = true;
                } else {
                    if ($opts['compress'] === true) {
                        $opts['compress'] = self::DEFAULT_COMPRESS;
                    }
                    $compress = strlen($packed) > $opts['compress'];
                }

                if ($compress) {
                    $packed = self::$_compress->compress($packed);
                    $key |= self::COMPRESS_MASK;
                }
            }

            return pack('C', $key) . $packed;
        }

        throw new Horde_Pack_Exception('Could not pack data.');
    }

    /**
     * Unpack a string.
     *
     * @param string $data  The packed string.
     *
     * @return mixed  The unpacked data.
     * @throws Horde_Pack_Exception
     */
    public function unpack($data)
    {
        if ($data && is_string($data)) {
            $mask = unpack('C*', $data[0]);
            $mask = reset($mask);
            $data = substr($data, 1);

            if ($mask & self::COMPRESS_MASK) {
                $data = self::$_compress->decompress($data);
                $mask ^= self::COMPRESS_MASK;
            }

            if (isset(self::$_drivers[$mask])) {
                return self::$_drivers[$mask]->unpack($data);
            }
        }

        throw new Horde_Pack_Exception('Could not unpack data.');
    }

}
