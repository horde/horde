<?php
/**
 * Copyright 2006-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd
 * @package  Db
 */

/**
 * Encapsulation object for binary values to be used in SQL statements to
 * ensure proper quoting, escaping, retrieval, etc.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2006-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd
 * @package   Db
 * @property  $value  The binary value as a string. @since Horde_Db 2.1.0
 * @property  $stream  The binary value as a stream. @since Horde_Db 2.4.0
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
