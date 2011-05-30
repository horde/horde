<?php

include 'Horde/Autoloader/Default.php';

class TestTask implements Horde_Queue_Task
{
    public function run()
    {
        echo "executing\n";
    }
}

$qs = new Horde_Queue_Storage_Array();
$qr = new Horde_Queue_Runner_RequestShutdown($qs);

$qs->add(new TestTask());

echo "exiting\n";
exit(0);
