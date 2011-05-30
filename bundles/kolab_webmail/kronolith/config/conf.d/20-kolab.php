<?php
$conf['calendar']['driver'] = 'kolab';
$conf['storage']['driver'] = 'kolab';
$conf['storage']['freebusy']['protocol'] = 'https';
$conf['storage']['freebusy']['port'] = 443;
$conf['authenticated_freebusy'] = true;
$conf['storage']['default_domain'] = $GLOBALS['conf']['kolab']['primary_domain'];
$conf['reminder']['server_name'] = $GLOBALS['conf']['kolab']['primary_domain'];
$conf['reminder']['from_addr'] = 'postmaster@' . $GLOBALS['conf']['kolab']['primary_domain'];
