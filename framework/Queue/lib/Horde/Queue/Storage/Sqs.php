<?php
// TEST THIS AGAINST LOCAL https://github.com/iriscouch/cqs
/**
 * Stores queue tasks in Amazon's SQS.
 */
class Horde_Queue_Storage_Sqs extends Horde_Queue_Storage_Base
{
    public function add($task)
    {
    }

    public function getMany($num = 50)
    {
    }
}
