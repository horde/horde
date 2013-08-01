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
 * @package   Compress_Fast
 */

/**
 * Abstract base driver class for fast compression.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress_Fast
 */
abstract class Horde_Compress_Fast_Base
{
    /**
     * Is this driver supported on this system?
     *
     * @return boolean  True if supported.
     */
    static public function supported()
    {
        return true;
    }

    /**
     * Compresses a string.
     *
     * @param string $text  The string to compress.
     *
     * @return string  The compressed string.
     * @throws Horde_Compress_Fast_Exception
     */
    abstract public function compress($text);

    /**
     * Decompresses a string.
     *
     * @param string $text  The compressed string.
     *
     * @return string  The decompressed string.
     * @throws Horde_Compress_Fast_Exception
     */
    abstract public function decompress($text);

}
