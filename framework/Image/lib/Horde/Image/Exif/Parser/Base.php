<?php
/**
 * Copyright 2009-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Image
 */

/**
 * TODO
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2009-2016 Horde LLC
 * @package   Image
 */
class Horde_Image_Exif_Parser_Base
{
    /**
     * Look up the data type.
     *
     * @param string  The string representation of the hex type code.
     *
     * @return array  An array containing the string type name in the 0 index
     *                and the integer size in the 1 index.
     */
    protected function _lookupType($type)
    {
        switch($type) {
        case '0001': $type = 'UBYTE';          $size = 1; break;
        case '0002': $type = 'ASCII';          $size = 1; break;
        case '0003': $type = 'USHORT';         $size = 2; break;
        case '0004': $type = 'ULONG';          $size = 4; break;
        case '0005': $type = 'URATIONAL';      $size = 8; break;
        case '0006': $type = 'SBYTE';          $size = 1; break;
        case '0007': $type = 'UNDEFINED';      $size = 1; break;
        case '0008': $type = 'SSHORT';         $size = 2; break;
        case '0009': $type = 'SLONG';          $size = 4; break;
        case '000a': $type = 'SRATIONAL';      $size = 8; break;
        case '000b': $type = 'FLOAT';          $size = 4; break;
        case '000c': $type = 'DOUBLE';         $size = 8; break;
        default:     $type = 'error:' . $type; $size = 0; break;
        }

        return array($type, $size);
    }
}