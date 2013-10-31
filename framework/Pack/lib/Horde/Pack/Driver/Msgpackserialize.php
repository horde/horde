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
 * PHP msgpack extension handler (serialized methods).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */
class Horde_Pack_Driver_Msgpackserialize extends Horde_Pack_Driver
{
    /**
     */
    protected $_id = 8;

    /**
     * @todo This theoretically should work, but I haven't been able to
     * do it so far.
     */
    protected $_phpob = false;

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
        return msgpack_serialize($data);
    }

    /**
     */
    public function unpack($data)
    {
        return msgpack_unserialize($data);
    }

}
