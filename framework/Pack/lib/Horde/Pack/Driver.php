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
 * Abstract driver class for implementing a pack driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 *
 * @property-read integer $id  The single-byte identifier for this driver
 *                             (also used as priority).
 * @property-read boolean $phpob  Supports packing PHP objects?
 */
abstract class Horde_Pack_Driver
{
    /**
     * Identifer for this driver. Each driver needs a unique priority.
     *
     * @var integer
     */
    protected $_id = 0;

    /**
     * Does this driver support packing PHP objects?
     *
     * @var boolean
     */
    protected $_phpob = false;

    /**
     * Is this driver supported on this system?
     *
     * @return boolean  True if supported.
     */
    public static function supported()
    {
        return true;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'id':
            return $this->_id;

        case 'phpob':
            return $this->_phpob;
        }
    }

    /**
     * Pack a string.
     *
     * @param mixed $data  The data to pack.
     *
     * @return string  The packed string.
     * @throws Horde_Pack_Exception
     */
    abstract public function pack($data);

    /**
     * Unpack a string.
     *
     * @param string $data  The packed string.
     *
     * @return mixed  The unpacked data.
     * @throws Horde_Pack_Exception
     */
    abstract public function unpack($data);

}
