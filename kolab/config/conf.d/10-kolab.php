<?php

$conf['auth']['driver'] = 'kolab';
$conf['auth']['admins'] = array('manager');

//@todo: Fix Kolab driver
//$conf['group']['driver'] = 'kolab';
$conf['group']['driver'] = 'mock';

$conf['perms']['driverconfig'] = 'horde';
$conf['perms']['driver'] = 'Null';

$conf['share']['driver'] = 'kolab';

$conf['mailer']['type'] = 'smtp';
$conf['mailer']['params']['auth'] = true;
$conf['mailer']['params']['port'] = 25;
// @todo: Reactivate for Kolab Server 2.3.
//$conf['mailer']['params']['port'] = 587;

$conf['accounts']['driver'] = 'kolab';
$conf['accounts']['params']['attr'] = 'mail';
$conf['accounts']['params']['strip'] = false;

$conf['kolab']['enabled'] = true;
