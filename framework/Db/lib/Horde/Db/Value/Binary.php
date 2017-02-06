<?php
/**
 * Copyright 2006-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd
 * @category Horde
 * @package  Db
 */

/**
 * Encapsulation object for binary values to be used in SQL statements to
 * ensure proper quoting, escaping, retrieval, etc.
 *
 * @property $value  The binary value as a string. @since Horde_Db 2.1.0
 * @property $stream  The binary value as a stream. @since Horde_Db 2.4.0
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd
 * @category Horde
 * @package  Db
 */
class Horde_Db_Value_Binary extends Horde_Db_Value_Lob
{
    /**
     * @param Horde_Db_Adapter $db
     */
    public function quote(Horde_Db_Adapter $db)
    {
        return $db->quoteBinary($this->value);
    }
}
