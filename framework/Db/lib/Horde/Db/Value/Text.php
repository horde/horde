<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/bsd
 * @category Horde
 * @package  Db
 */

/**
 * Encapsulation object for long text values to be used in SQL statements to
 * ensure proper quoting, escaping, retrieval, etc.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/bsd
 * @category Horde
 * @package  Db
 */
class Horde_Db_Value_Text extends Horde_Db_Value_Lob
{
    /**
     * @param Horde_Db_Adapter $db
     */
    public function quote(Horde_Db_Adapter $db)
    {
        return $db->quoteString($this->value);
    }
}
