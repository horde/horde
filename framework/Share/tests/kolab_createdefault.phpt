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
                               
$shares = Horde_Share::singleton('kronolith', 'kolab');

class Perms_mock 
{
    function &newPermission($name) {
        $perms = array(
            'users' => array(
                Horde_Auth::getAuth() => Horde_Perms::SHOW | Horde_Perms::READ |
                Horde_Perms::EDIT | Horde_Perms::DELETE));
        $result = &new Horde_Perms_Permission($name, $perms);
        return $result;
    }
}

$GLOBALS['perms'] = &new Perms_mock();

$default = $shares->getDefaultShare();

echo $default->getName() . "\n";

$shares = Horde_Share::singleton('turba', 'kolab');

$default = $shares->getDefaultShare();

echo $default->get('name') . "\n";

?>
--EXPECT--
wrobel@example.org
Contacts
