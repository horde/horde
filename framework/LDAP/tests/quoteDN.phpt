--TEST--
Horde_LDAP::quoteDN() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../LDAP.php';

echo Horde_LDAP::quoteDN(array(array('cn', 'John Smith'),
                               array('dc', 'example'),
                               array('dc', 'com'))) . "\n";

echo Horde_LDAP::quoteDN(array(array('cn', 'Smith, John'),
                               array('dc', 'example'),
                               array('dc', 'com'))) . "\n";

echo Horde_LDAP::quoteDN(array(array('cn', ' John Smith'),
                               array('dc', 'example'),
                               array('dc', 'com'))) . "\n";

echo Horde_LDAP::quoteDN(array(array('cn', 'John Smith '),
                               array('dc', 'example'),
                               array('dc', 'com'))) . "\n";

echo Horde_LDAP::quoteDN(array(array('cn', 'John  Smith'),
                               array('dc', 'example'),
                               array('dc', 'com'))) . "\n";

echo Horde_LDAP::quoteDN(array(array('cn', 'John+Smith'),
                               array('dc', 'example'),
                               array('dc', 'com'))) . "\n";

echo Horde_LDAP::quoteDN(array(array('cn', 'John "Bugsy" Smith'),
                               array('dc', 'example'),
                               array('dc', 'com'))) . "\n";

?>
--EXPECT--
cn=John Smith,dc=example,dc=com
cn="Smith, John",dc=example,dc=com
cn=" John Smith",dc=example,dc=com
cn="John Smith ",dc=example,dc=com
cn="John  Smith",dc=example,dc=com
cn="John+Smith",dc=example,dc=com
cn="John \"Bugsy\" Smith",dc=example,dc=com
