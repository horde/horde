<?php

include 'Horde/Autoloader/Default.php';

class TestTask implements Horde_Queue_Task
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function run()
    {
        if ($this->name == 'a') {
            global $qs;
            $qs->add(new TestTask('b'));
        }
        echo "executing " . $this->name . "\n";
    }
}

$qs = new Horde_Queue_Storage_Array();
$qr = new Horde_Queue_Runner_RequestShutdown($qs);

$qs->add(new TestTask('a'));

echo "exiting\n";
exit(0);
