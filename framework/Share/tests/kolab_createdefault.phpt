--TEST--
Check the Kolab Share handler
--FILE--
<?php

require_once 'Horde/Kolab/Test/Storage.php';
$test = new Horde_Kolab_Test_Storage();

$world = $test->prepareBasicSetup();

$test->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                               array('password' => 'none')));

$test->prepareNewFolder($world['storage'], 'Contacts', 'contact', true);

require_once dirname(__FILE__) . '/../Share.php';

$shares = new Horde_Share_Kolab('kronolith');

class Perms_mock
{
    function &newPermission($name) {
        $perms = array(
            'users' => array(
                $GLOBALS['registry']->getAuth() => Horde_Perms::SHOW | Horde_Perms::READ |
                Horde_Perms::EDIT | Horde_Perms::DELETE));
        $result = &new Horde_Perms_Permission($name, $perms);
        return $result;
    }
}

$GLOBALS['perms'] = &new Perms_mock();

$default = $shares->getDefaultShare();

echo $default->getName() . "\n";

$shares = new Horde_Share_Kolab('turba');

$default = $shares->getDefaultShare();

echo $default->get('name') . "\n";

?>
--EXPECT--
wrobel@example.org
Contacts
