<?php
/**
 * Test the composite server.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../TestCase.php';

/**
 * Test the composite server.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Composite_BaseTest extends Horde_Kolab_Server_TestCase
{
    public function testMethodGetReturnsServerElement()
    {
        $composite = $this->getMockedComposite();
        $this->assertType('Horde_Kolab_Server_Interface', $composite->server);
        $this->assertType('Horde_Kolab_Server_Objects_Interface', $composite->objects);
        $this->assertType('Horde_Kolab_Server_Structure_Interface', $composite->structure);
        $this->assertType('Horde_Kolab_Server_Search_Interface', $composite->search);
        $this->assertType('Horde_Kolab_Server_Schema_Interface', $composite->schema);
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