<?php
/**
 * Test the handling of list queries.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the handling of list queries.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_List_QueriesTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Horde_Kolab_Storage_List_Exception
     */
    public function testEmptyQueries()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $this->assertEquals(array(), $list->getQuery('TEST'));
    }

    public function testRegisterQuery()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $query = $this->getMock('Horde_Kolab_Storage_List_QUery');
        $list->registerQuery('TEST', $query);
        $this->assertSame($query, $list->getQuery('TEST'));
    }

    public function testCreateFolder()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $query = $this->getMock('Horde_Kolab_Storage_List_QUery');
        $query->expects($this->once())
            ->method('createFolder')
            ->with('TEST');
        $list->registerQuery('TEST', $query);
        $list->createFolder('TEST');
    }

    public function testDeleteFolder()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $query = $this->getMock('Horde_Kolab_Storage_List_QUery');
        $query->expects($this->once())
            ->method('deleteFolder')
            ->with('TEST');
        $list->registerQuery('TEST', $query);
        $list->deleteFolder('TEST');
    }

    public function testRenameFolder()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $query = $this->getMock('Horde_Kolab_Storage_List_QUery');
        $query->expects($this->once())
            ->method('renameFolder')
            ->with('FOO', 'BAR');
        $list->registerQuery('TEST', $query);
        $list->renameFolder('FOO', 'BAR');
    }

    public function testSynchronize()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $query = $this->getMock('Horde_Kolab_Storage_List_QUery');
        $query->expects($this->once())
            ->method('synchronize');
        $list->registerQuery('TEST', $query);
        $list->synchronize();
    }
}