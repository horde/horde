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
 * PHP msgpack extension handler (non-serialized methods).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */
class Horde_Pack_Driver_Msgpack extends Horde_Pack_Driver
{
    /**
     */
    protected $_id = 16;

    /**
     */
    static public function supported()
    {
        return extension_loaded('msgpack');
    }

    /**
     */
    public function pack($data)
    {
        return msgpack_pack($data);
    }

    /**
     */
    public function unpack($data)
    {
        return msgpack_unpack($data);
    }

}
