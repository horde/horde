<?php
/**
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Rdo
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Rdo
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

}
