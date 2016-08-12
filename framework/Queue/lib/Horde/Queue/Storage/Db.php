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
    /**
     * @var queue
     */
    protected $_queue;

    /**
     * @param Horde_Db_Adapter $db  The DB interface to use
     * @param string $queue         Optional: Use a separate queue
     */
    public function __construct(Horde_Db_Adapter $db, $queue = 'default')
    {
        $this->_db = $db;
        $this->setQueue($queue);
    }

    /**
     *
     * @param string $queue  The name of the queue to add/remove tasks
     */
    public function setQueue($queue = 'default')
    {
        $this->_queue = $queue;
    }

    /**
     * Serialize a task to the database
     * @param  Horde_Queue_Task $task  A task to serialize
     * @throws Horde_Queue_Exception
     */
    public function add(Horde_Queue_Task $task)
    {
        $values = array($this->_queue, serialize($task));
        $query = 'INSERT INTO horde_queue_tasks (task_queue, task_fields) VALUES(?, ?)';
        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Queue_Exception($e);
        }
    }

    public function getMany($num = 50)
    {
        $tasks = array();
        $values = array();
        $query = 'SELECT * FROM horde_queue_tasks where task_queue = ? ORDER BY task_id LIMIT ?';
        $values[] = $this->_queue;
        $values[] = $num;
        try {
            $rows = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Queue_Exception($e);
        }

        $query = 'DELETE FROM horde_queue_tasks WHERE task_id = ?';
        foreach ($rows as $row) {
            $tasks[] = unserialize($row['task_fields']);
            // TODO: Evaluate if a single call for all IDs is faster for
            // various scenarios
            try {
                $this->_db->execute($query, array($row['task_id']));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Queue_Exception($e);
            }
        }
        return $tasks;
    }

}
