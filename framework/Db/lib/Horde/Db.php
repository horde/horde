<?php
/**
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Db
 */

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Db
 */
class Horde_Db
{
    /**
     * Specifies that the fetch method shall return each row as an array
     * indexed by column name as returned in the corresponding result set.
     */
    const FETCH_ASSOC = 2;

    /**
     * Specifies that the fetch method shall return each row as an array
     * indexed by column number as returned in the corresponding result set,
     * starting at column 0.
     */
    const FETCH_NUM = 3;

    /**
     * Specifies that the fetch method shall return each row as an array
     * indexed by both column name and number as returned in the corresponding
     * result set, starting at column 0.
     */
    const FETCH_BOTH = 4;
}
