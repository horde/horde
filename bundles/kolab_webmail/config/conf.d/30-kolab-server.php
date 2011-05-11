<?php

$conf['problems']['email'] = 'postmaster@example.com';
$conf['problems']['maildomain'] = 'example.com';
$conf['kolab']['primary_domain'] = 'example.com';
//@todo: secure!
$conf['kolab']['ldap']['server'] = 'ldap://localhost:389';
$conf['kolab']['ldap']['hostname'] = 'localhost';
$conf['kolab']['ldap']['port'] = 389;
$conf['kolab']['ldap']['basedn'] = 'dc=example,dc=com';
$conf['kolab']['ldap']['phpdn'] = 'cn=nobody,cn=internal,dc=example,dc=com';
$conf['kolab']['ldap']['phppw'] = 'SECRET';
$conf['kolab']['imap']['server'] = 'localhost';
$conf['kolab']['imap']['port'] = 993;
$conf['kolab']['imap']['sieveport'] = 2000;
$conf['kolab']['imap']['maildomain'] = 'localhost';
