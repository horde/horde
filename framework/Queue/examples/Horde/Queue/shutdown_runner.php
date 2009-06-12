<?php

set_include_path(get_include_path() . ':' . dirname(__FILE__) . '/../../../lib');
include 'Horde/Autoloader.php';

class TestTask extends Horde_Queue_Task_Base {
    public function run()
    {
        echo "executing\n";
    }
}

$qs = new Horde_Queue_Storage_RequestOnly();
$qr = new Horde_Queue_Runner_RequestShutdown($qs);

$qs->add(new TestTask());

echo "exiting\n";
exit(0);
