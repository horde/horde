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
class Horde_Db_Value_Text implements Horde_Db_Value
{
    /**
     * Text value to be quoted
     *
     * @var string
     * @since Horde_Db 2.1.0
     */
    public $value;

    /**
     * Constructor
     *
     * @param string|stream  $textValue  The text value in a string or stream
     *            resource.
     */
    public function __construct($textValue)
    {
        $this->value = $textValue;
    }

    /**
     * @param Horde_Db_Adapter $db
     */
    public function quote(Horde_Db_Adapter $db)
    {
        if (is_resource($this->value)) {
            rewind($this->value);
            return $db->quoteString(stream_get_contents($this->value));
        }
        return $db->quoteString($this->value);
    }
}
