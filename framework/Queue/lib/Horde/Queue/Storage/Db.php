<?php
/**
 * Stores queue tasks in a database table.
 */
class Horde_Queue_Storage_Db extends Horde_Queue_Storage
{
    /**
     * @var Horde_Db_Adapter
     */
    protected $_db;

    public function __construct(Horde_Db_Adapter $db)
    {
        $this->_db = $db;
    }

    public function add(Horde_Queue_Task $task)
    {
    }

    public function getMany($num = 50)
    {
    }
}
