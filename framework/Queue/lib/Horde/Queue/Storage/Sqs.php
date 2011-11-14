<?php
// TEST THIS AGAINST LOCAL https://github.com/iriscouch/cqs
/**
 * Stores queue tasks in Amazon's SQS.
 */
class Horde_Queue_Storage_Sqs extends Horde_Queue_Storage
{
    public function add(Horde_Queue_Storage $task)
    {
    }

    public function getMany($num = 50)
    {
    }
}
