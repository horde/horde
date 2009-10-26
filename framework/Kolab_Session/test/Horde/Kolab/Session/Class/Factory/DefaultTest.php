<?php
/**
 * Test the default factory.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the default factory.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Class_Factory_DefaultTest extends Horde_Kolab_Session_SessionTestCase
{
    public function testMethodGetserverHasResultHordekolabserver()
    {
        $this->markTestIncomplete('The factory in the Kolab_Server package needs to be fixed.');
        $server = $this->getMock('Horde_Kolab_Server');
        $server_factory = $this->getMock('Horde_Kolab_Server_Factory');
        $server_factory->expects($this->once())
            ->method('getServer')
            ->will($this->returnValue($server));
        $factory = new Horde_Kolab_Session_Factory_Default(
            array('server' => array()),
            $server_factory
        );
        $this->assertType('Horde_Kolab_Server', $factory->getServer());
    }

    public function testMethodGetsessionauthHasResultHordekolabsessionauth()
    {
        $factory = new Horde_Kolab_Session_Factory_Default(
            array('server' => array()),
            $this->getMock('Horde_Kolab_Server_Factory')
        );
        $this->assertType('Horde_Kolab_Session_Auth', $factory->getSessionAuth());
    }

    public function testMethodGetsessionconfigurationHasResultArray()
    {
        $factory = new Horde_Kolab_Session_Factory_Default(
            array('server' => array()),
            $this->getMock('Horde_Kolab_Server_Factory')
        );
        $this->assertType('array', $factory->getSessionConfiguration());
    }

    public function testMethodGetsessionstorageHasResultHordekolabsessionstorage()
    {
        $factory = new Horde_Kolab_Session_Factory_Default(
            array('server' => array()),
            $this->getMock('Horde_Kolab_Server_Factory')
        );
        $this->assertType('Horde_Kolab_Session_Storage', $factory->getSessionStorage());
    }
}