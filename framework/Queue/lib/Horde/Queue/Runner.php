<?php
abstract class Horde_Queue_Runner
{
    /**
     * @var Horde_Queue_Storage
     */
    protected $_storage;

    public function __construct(Horde_Queue_Storage $storage)
    {
        $this->_storage = $storage;
    }

    abstract public function run();
}
