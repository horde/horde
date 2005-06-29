<?php
/* CONFIG START. DO NOT CHANGE ANYTHING IN OR AFTER THIS LINE. */
// $Horde: horde/config/conf.xml,v 1.74.2.5 2005/03/22 11:40:14 jan Exp $
$conf['storage']['admins'] = array('andrew.klang@v-office.biz');
$conf['storage']['params']['hostspec'] = 'localhost';
$conf['storage']['params']['basedn'] = 'dc=alkaloid,dc=net';
$conf['storage']['params']['binddn'] = 'uid=Horde,ou=Service Accounts,dc=alkaloid,dc=net';
$conf['storage']['params']['password'] = 'InsaneMasses';
$conf['storage']['params']['version'] = '3';
$conf['storage']['params']['uid'] = 'mail';
$conf['storage']['params']['objectclass'] = array('hordePerson');
$conf['storage']['params']['filter_type'] = 'objectclass';
$conf['storage']['driver'] = 'ldap';
/* CONFIG END. DO NOT CHANGE ANYTHING IN OR BEFORE THIS LINE. */
