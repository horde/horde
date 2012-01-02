<?php
/**
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 */

/**
 * Interface for values with specific quoting rules.
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 */
interface Horde_Db_Value
{
    /**
     * @param Horde_Db_Adapter $db
     */
    public function quote(Horde_Db_Adapter $db);
}
