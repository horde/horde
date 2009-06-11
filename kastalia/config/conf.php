<?php
/* CONFIG START. DO NOT CHANGE ANYTHING IN OR AFTER THIS LINE. */
// $Horde: kastalia/config/conf.xml,v 1.0 2008/09/16 09:43:44 sqall Exp $
$conf['datastore']['location'] = '/home/sqall/projekte/www/horde/kastalia/datastore';
$conf['datastore']['directoryexcludes'] = array('lost+found', 'testordner', 'testordner2');
$conf['upload']['uploadenabled'] = true;
$conf['upload']['maxfilesize'] = 134217728;
$conf['upload']['tempdir'] = '/home/sqall/projekte/www/horde/kastalia/temp';
$conf['upload']['tempctime'] = 10;
$conf['upload']['securestore'] = true;
$conf['upload']['memorysize'] = 3145728;
$conf['upload']['refreshcycle'] = 1;
/* CONFIG END. DO NOT CHANGE ANYTHING IN OR BEFORE THIS LINE. */