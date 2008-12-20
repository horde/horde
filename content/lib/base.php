<?php

require dirname(__FILE__) . '/../../../lib/base.php';
require 'Horde/Autoloader.php';
$CONTENT_DIR = dirname(__FILE__) . '/../';

$conf['sql']['adapter'] = $conf['sql']['phptype'] == 'mysqli' ? 'mysqli' : 'pdo_' . $conf['sql']['phptype'];
Horde_Rdo::setAdapter(Horde_Rdo_Adapter::factory('pdo', $conf['sql']));
Horde_Db::setAdapter(Horde_Db_Adapter::factory($conf['sql']));
