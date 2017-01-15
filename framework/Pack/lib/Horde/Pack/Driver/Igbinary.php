<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */

/**
 * PHP igbinary extension handler.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */
class Horde_Pack_Driver_Igbinary extends Horde_Pack_Driver
{
    /**
     */
    protected $_id = 4;

    /**
     */
    protected $_phpob = true;

    /**
     */
    public static function supported()
    {
        return extension_loaded('igbinary');
    }

    /**
     */
    public function pack($data)
    {
        return igbinary_serialize($data);
    }

    /**
     */
    public function unpack($data)
    {
        $out = igbinary_unserialize($data);
        if (!is_null($out) || ($data == igbinary_serialize(null))) {
            return $out;
        }

        throw new Horde_Pack_Exception('Error when unpacking serialized data.');
    }

}
