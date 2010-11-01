<?php

set_include_path(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/lib:' . get_include_path());
require 'Horde/Autoloader/Default.php';

$t = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'this fortnight';
$opts = count($_SERVER['argv']) > 1 ? array() : array('now' => new Horde_Date('2006-08-16 14:00:00'));

$d = Horde_Date_Parser::parse($t, $opts);
echo "$d\n";
