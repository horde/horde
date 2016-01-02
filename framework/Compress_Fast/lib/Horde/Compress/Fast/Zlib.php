<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress_Fast
 */

/**
 * Supports using the zlib extension for compression.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress_Fast
 * @since     1.1.0
 */
class Horde_Compress_Fast_Zlib extends Horde_Compress_Fast_Base
{
    /**
     */
    public static function supported()
    {
        return extension_loaded('zlib');
    }

    /**
     */
    public function compress($text)
    {
        return strlen($text)
            ? gzdeflate($text)
            : '';
    }

    /**
     */
    public function decompress($text)
    {
        return strlen($text)
            ? @gzinflate($text)
            : '';
    }

}
