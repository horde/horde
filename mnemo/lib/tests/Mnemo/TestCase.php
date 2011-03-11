<?php
/**
 * Basic Mnemo test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Mnemo
 */

/**
 * Basic Mnemo test case.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Mnemo
 */
class Mnemo_TestCase
extends PHPUnit_Framework_TestCase
{
    protected function getInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }

    protected function getKolabDriver()
    {
        $GLOBALS['injector'] = $this->getInjector();
        $kolab_factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'queryset' => array('list' => array('queryset' => 'horde')),
                'params' => array(
                    'username' => 'test@example.com',
                    'host' => 'localhost',
                    'port' => 143,
                    'data' => array(
                        'user/test' => array(
                            'permissions' => array('anyone' => 'alrid')
                        )
                    )
                )
            )
        );
        $storage = $kolab_factory->create();
        $GLOBALS['injector']->setInstance('Horde_Kolab_Storage', $storage);
        $GLOBALS['injector']->setInstance('Horde_History', new Horde_History_Mock('test@example.com'));
        $factory = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver');
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $GLOBALS['mnemo_shares'] = new Horde_Share_Kolab(
            'mnemo', 'test@example.com', new Horde_Perms(), new Horde_Group_Mock()
        );
        $GLOBALS['mnemo_shares']->setStorage($storage);
        $share = $GLOBALS['mnemo_shares']->newShare(
            'test@example.com',
            strval(new Horde_Support_Randomid()),
            "Notepad of Tester"
        );
        $GLOBALS['mnemo_shares']->addShare($share);
        return $factory->create($share->getName());
    }
}