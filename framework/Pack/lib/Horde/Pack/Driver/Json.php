<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */

/**
 * PHP built-in json_encode()/json_decode() handler.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */
class Horde_Pack_Driver_Json extends Horde_Pack_Driver
{
    /**
     */
    protected $_id = 2;

    /**
     */
    public static function supported()
    {
        return extension_loaded('json');
    }

    /**
     */
    public function pack($data)
    {
        static $jsonc_bug = null;

        $old_error = error_reporting(0);
        $d = json_encode($data);
        error_reporting($old_error);
        // TODO: JSON_ERROR_UTF8 = 5; available as of PHP 5.3.3
        if (json_last_error() === 5) {
            throw new Horde_Pack_Exception(
                'Non UTF-8 data cannot be JSON packed.'
            );
        }

        if (is_null($jsonc_bug)) {
            $orig = array("A\0B" => "A\0B");
            $jsonc_bug = (json_decode(json_encode($orig), true) !== $orig);
        }

        /* JSON-C (used in, e.g., Debian/Ubuntu) is broken when it comes to
         * handling null characters. If we detect the buggy behavior and
         * we see a null character in the output, use a different packer. */
        if ($jsonc_bug && (strpos($d, "\u0000") !== false)) {
            throw new Horde_Pack_Exception(
                'JSON decoder is broken (invalid handling of null chars).'
            );
        }

        /* For JSON, we need to keep track whether the initial data was
         * an object or class. The initial JSON character is one of the
         * following:
         *   0: Non-array
         *   1: Array */
        return intval(is_array($data)) . $d;
    }

    /**
     */
    public function unpack($data)
    {
        $out = json_decode(substr($data, 1), ($data[0] == 1));
        if (!is_null($out) || (json_last_error() === JSON_ERROR_NONE)) {
            return $out;
        }

        throw new Horde_Pack_Exception('Error when unpacking JSON data.');
    }

}
