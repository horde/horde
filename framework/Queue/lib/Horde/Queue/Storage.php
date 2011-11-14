<?php
abstract class Horde_Queue_Storage
{
    abstract public function add(Horde_Queue_Task $task);
}
