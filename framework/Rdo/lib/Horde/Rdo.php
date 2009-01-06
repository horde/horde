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
class Horde_Rdo
{
    /**
     * One-to-one relationships.
     */
    const ONE_TO_ONE = 1;

    /**
     * One-to-many relationships (this object has many children).
     */
    const ONE_TO_MANY = 2;

    /**
     * Many-to-one relationships (this object is one of many children
     * of a single parent).
     */
    const MANY_TO_ONE = 3;

    /**
     * Many-to-many relationships (this object relates to many
     * objects, each of which relate to many objects of this type).
     */
    const MANY_TO_MANY = 4;

    /**
     * Global inflector object.
     *
     * @var Horde_Support_Inflector
     */
    protected static $_inflector;

    /**
     * Get the global inflector object.
     *
     * @return Horde_Support_Inflector
     */
    public static function getInflector()
    {
        if (!self::$_inflector) {
            self::$_inflector = new Horde_Support_Inflector;
        }
        return self::$_inflector;
    }

    /**
     * Set a custom global inflector.
     *
     * @param Horde_Support_Inflector $inflector
     */
    public static function setInflector($inflector)
    {
        self::$_inflector = $inflector;
    }

}
