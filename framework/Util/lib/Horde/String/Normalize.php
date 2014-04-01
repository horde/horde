<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Util
 */

/**
 * Provides utility methods used to normalize a string.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Util
 * @since     2.4.0
 */
class Horde_String_Normalize
{
    /**
     * Normalize mapping cache.
     *
     * @var array
     */
    static protected $_map;

    /**
     * Normalize a UTF-8 string to ASCII, replacing non-English characters to
     * their English equivalents.
     *
     * Note: there is no guarantee that the output string will be ASCII-only,
     * since any non-ASCII character not in the transliteration list will
     * be ignored.
     *
     * @param string $str  Input string (UTF-8).
     *
     * @return string  Normalized string (UTF-8).
     */
    static public function normalizeToAscii($str)
    {
        if (!isset(self::$_map)) {
            self::$_map = array(
                'À' => 'A',
                'Á' => 'A',
                'Â' => 'A',
                'Ã' => 'A',
                'Ä' => 'A',
                'Å' => 'A',
                'Æ' => 'Ae',
                'à' => 'a',
                'á' => 'a',
                'â' => 'a',
                'ã' => 'a',
                'ä' => 'a',
                'å' => 'a',
                'æ' => 'ae',
                'Þ' => 'B',
                'þ' => 'b',
                'Ç' => 'C',
                'ç' => 'c',
                'Ð' => 'Dj',
                'È' => 'E',
                'É' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'è' => 'e',
                'é' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'ƒ' => 'f',
                'Ì' => 'I',
                'Í' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'ì' => 'i',
                'í' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'Ñ' => 'N',
                'ñ' => 'n',
                'Ò' => 'O',
                'Ó' => 'O',
                'Ô' => 'O',
                'Õ' => 'O',
                'Ö' => 'O',
                'Ø' => 'O',
                'ð' => 'o',
                'ò' => 'o',
                'ó' => 'o',
                'ô' => 'o',
                'õ' => 'o',
                'ö' => 'o',
                'ø' => 'o',
                'Š' => 'S',
                'ß' => 'Ss',
                'š' => 's',
                'ś' => 's',
                'Ù' => 'U',
                'Ú' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'ù' => 'u',
                'ú' => 'u',
                'û' => 'u',
                'Ý' => 'Y',
                'ý' => 'y',
                'ÿ' => 'y',
                'Ž' => 'Z',
                'ž' => 'z'
            );
        }

        return strtr($str, self::$_map);
    }

}
