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
 * PHP built-in serialize()/unserialize() handler.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */
class Horde_Pack_Driver_Serialize extends Horde_Pack_Driver
{
    /**
     */
    protected $_id = 1;

    /**
     */
    protected $_phpob = true;

    /**
     */
    public function pack($data)
    {
        return serialize($data);
    }

    /**
     */
    public function unpack($data)
    {
        $out = @unserialize($data);
        if (($out !== false) || ($data == serialize(false))) {
            return $out;
        }

        throw new Horde_Pack_Exception('Error when unpacking serialized data.');
    }

}
