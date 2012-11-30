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

    public function runTask(Horde_Queue_Task $task)
    {
        //@TODO add logging
        try {
            $task->run();
        } catch (Exception $e) {
            //@TODO TEMPORARY
            echo $e->getMessage() . "\n";
        }
    }
}
