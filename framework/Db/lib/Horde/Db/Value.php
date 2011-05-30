<?php
/**
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Db
 */

/**
 * Interface for values with specific quoting rules.
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
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
