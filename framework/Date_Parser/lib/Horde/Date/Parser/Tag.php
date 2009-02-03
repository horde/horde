<?php
/**
 */

/**
 * Tokens are tagged with subclassed instances of this class when they match
 * specific criteria.
 */
class Horde_Date_Parser_Tag
{
    public $type;
    public $now;

    public function __construct($type)
    {
        $this->type = $type;
    }

}
