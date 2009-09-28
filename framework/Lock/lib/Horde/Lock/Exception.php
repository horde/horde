<?php
class Horde_Lock_Exception extends Exception
{
    /**
     */
    public function __construct($msg, $code = 0)
    {
        parent::__construct($msg, $code);
    }

}
