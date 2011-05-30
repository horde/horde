<?php

$conf['auth']['driver'] = 'kolab';
$conf['auth']['admins'] = array('manager');

//@todo: Fix Kolab driver
//$conf['group']['driver'] = 'kolab';
$conf['group']['driver'] = 'Mock';
//@todo: check
//$conf['group']['cache'] = true;

$conf['perms']['driverconfig'] = 'horde';
$conf['perms']['driver'] = 'Sql';

$conf['prefs']['driver'] = 'KolabImap';

$conf['share']['driver'] = 'Kolab';
$conf['share']['auto_create'] = true;
//@todo: check
//$conf['share']['cache'] = true;

$conf['mailer']['type'] = 'smtp';
$conf['mailer']['params']['auth'] = true;
$conf['mailer']['params']['port'] = 25;
// @todo: Reactivate for Kolab Server 2.3.
//$conf['mailer']['params']['port'] = 587;

$conf['accounts']['driver'] = 'kolab';
$conf['accounts']['params']['attr'] = 'mail';
$conf['accounts']['params']['strip'] = false;

$conf['kolab']['enabled'] = true;

//@todo: Fix Kolab driver
$conf['vfs']['params']['driverconfig'] = 'horde';
//@todo: check
$conf['vfs']['type'] = 'Sql';
