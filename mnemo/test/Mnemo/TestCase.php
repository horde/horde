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
 * @license    http://www.horde.org/licenses/asl.php
 * @link       http://www.horde.org/apps/mnemo
 */

/**
 * Basic Mnemo test case.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/asl.php
 * @link       http://www.horde.org/apps/mnemo
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
        $this->getKolabFactory();
        $this->other_share = $GLOBALS['mnemo_shares']->newShare(
            'test@example.com',
            strval(new Horde_Support_Randomid()),
            "Other Notepad of Tester"
        );
        $GLOBALS['mnemo_shares']->addShare($this->other_share);
        return $this->factory->create($this->share->getName());
    }

    protected function getKolabFactory()
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
        $this->factory = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver');
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $GLOBALS['mnemo_shares'] = new Horde_Share_Kolab(
            'mnemo', 'test@example.com', new Horde_Perms_Null(), new Horde_Group_Mock()
        );
        $GLOBALS['mnemo_shares']->setStorage($storage);
        $this->share = $GLOBALS['mnemo_shares']->newShare(
            'test@example.com',
            strval(new Horde_Support_Randomid()),
            "Notepad of Tester"
        );
        $GLOBALS['mnemo_shares']->addShare($this->share);
        return $this->factory;
    }
}