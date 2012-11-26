<?php
class Horde_Queue_Runner_RequestShutdown extends Horde_Queue_Runner
{
    public function __construct(Horde_Queue_Storage $storage)
    {
        parent::__construct($storage);
        register_shutdown_function(array($this, 'run'));
    }

    public function run()
    {
        foreach ($this->_storage->getMany() as $task) {
            $task->run();
        }
    }
}
