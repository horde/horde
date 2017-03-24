<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd
 * @package  Db
 */

/**
 *
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd
 * @package   Db
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
