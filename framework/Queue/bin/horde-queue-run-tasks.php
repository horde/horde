#!/usr/bin/env php
<?php
/**
 * Copyright 2013-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

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

$runner = new Horde_Queue_Runner_RequestShutdown($storage);
