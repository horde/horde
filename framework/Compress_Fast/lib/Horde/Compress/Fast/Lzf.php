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
 * The lzf driver uses the lzf PECL module for compression.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress_Fast
 */
class Horde_Compress_Fast_Lzf extends Horde_Compress_Fast_Base
{
    /**
     */
    static public function supported()
    {
        return extension_loaded('lzf');
    }

    /**
     */
    public function compress($text)
    {
        return strlen($text)
            ? lzf_compress($text)
            : '';
    }

    /**
     */
    public function decompress($text)
    {
        return strlen($text)
            ? @lzf_decompress($text)
            : '';
    }

}
