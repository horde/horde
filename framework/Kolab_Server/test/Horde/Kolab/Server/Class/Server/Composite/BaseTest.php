<?php
/**
 * Test the composite server.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../TestCase.php';

/**
 * Test the composite server.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Composite_BaseTest extends Horde_Kolab_Server_TestCase
{
    public function testMethodGetReturnsServerElement()
    {
        $composite = $this->getMockedComposite();
        $this->assertInstanceOf('Horde_Kolab_Server_Interface', $composite->server);
        $this->assertInstanceOf('Horde_Kolab_Server_Objects_Interface', $composite->objects);
        $this->assertInstanceOf('Horde_Kolab_Server_Structure_Interface', $composite->structure);
        $this->assertInstanceOf('Horde_Kolab_Server_Search_Interface', $composite->search);
        $this->assertInstanceOf('Horde_Kolab_Server_Schema_Interface', $composite->schema);
        try {
            $a = $composite->something;
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(
                'Attribute something not supported!', $e->getMessage()
            );
        }
    }

    public function testMethodConnectHasPostconditionThatTheServerIsBound()
    {
        $composite = $this->getMockedComposite();
        $composite->server->expects($this->exactly(2))
            ->method('connectGuid');
        $composite->search->expects($this->exactly(1))
            ->method('__call')
            ->with('searchGuidForUidOrMail', array('user'));
        $composite->connect('user', 'pass');
    }
}