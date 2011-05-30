<?php
class Horde_Queue_Runner_Base
{
    protected $_storage;

    public function __construct($storage)
    {
        $this->_storage = $storage;
    }
}
