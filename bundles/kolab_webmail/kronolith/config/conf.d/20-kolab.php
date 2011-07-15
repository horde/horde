<?php
$conf['storage']['default_domain'] = $GLOBALS['conf']['kolab']['primary_domain'];
$conf['reminder']['server_name'] = $GLOBALS['conf']['kolab']['primary_domain'];
$conf['reminder']['from_addr'] = 'postmaster@' . $GLOBALS['conf']['kolab']['primary_domain'];
