<?php
/**
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Db
 */

/**
 * Horde_Db namespace - holds constants and global Db functions.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Db
 */
class Horde_Db
{
    /**
     * Global adapter object.
     *
     * @var Horde_Db_Adapter
     */
    protected static $_adapter;

    /**
     * Get the global adapter object.
     *
     * @return Horde_Db_Adapter
     */
    public static function getAdapter()
    {
        return self::$_adapter;
    }

    /**
     * Set a global database adapter.
     *
     * @param Horde_Db_Adapter $adapter
     */
    public static function setAdapter($adapter)
    {
        self::$_adapter = $adapter;
    }

}
