#!/usr/bin/env php
<?php
$baseFile = __DIR__ . '/../lib/Application.php';
if (file_exists($baseFile)) {
    require_once $baseFile;
} else {
    require_once 'PEAR/Config.php';
    require_once PEAR_Config::singleton()
        ->get('horde_dir', null, 'pear.horde.org') . '/lib/Application.php';
}
$queue = empty($argv[1]) ? 'default' : $argv[1];

Horde_Registry::appInit('horde', array('cli' => true, 'user_admin' => true));
$db = $injector->getInstance('Horde_Db_Adapter');
$storage = new Horde_Queue_Storage_Db($db, $queue);

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


$storage->add(new TestTask('A'));
$storage->add(new TestTask('B'));

$storage->setQueue('MyQueue');
$storage->add(new TestTask('AA'));
$storage->add(new TestTask('BB'));
