<?php
/**
 * Test the "time based" synchronization handler.
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
 * Test the "time based" synchronization handler.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Synchronization_TimeBasedTest
extends PHPUnit_Framework_TestCase
{
    public function testSynchronizeListReturn()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased();
        $list = $this->getMock(
            'Horde_Kolab_Storage_List_Tools', array(), array(), '', false, false
        );
        $list->expects($this->once())
            ->method('getListSynchronization')
            ->will($this->returnValue($this->getMock('Horde_Kolab_Storage_List_Synchronization')));
        $this->assertNull($synchronization->synchronizeList($list));
    }

    public function testListSynchronization()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased();
        $list = $this->getMock(
            'Horde_Kolab_Storage_List_Tools', array(), array(), '', false, false
        );
        $list->expects($this->once())
            ->method('getListSynchronization')
            ->will($this->returnValue($this->getMock('Horde_Kolab_Storage_List_Synchronization')));
        $synchronization->synchronizeList($list);
    }

    public function testListSynchronizationInSession()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased();
        $list = $this->getMock(
            'Horde_Kolab_Storage_List_Tools', array(), array(), '', false, false
        );
        $list->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('test'));
        $list->expects($this->once())
            ->method('getListSynchronization')
            ->will($this->returnValue($this->getMock('Horde_Kolab_Storage_List_Synchronization')));
        $synchronization->synchronizeList($list);
        $this->assertTrue($_SESSION['kolab_storage']['synchronization']['list']['test']);
    }

    public function testDuplicateListSynchronization()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased();
        $list = $this->getMock(
            'Horde_Kolab_Storage_List_Tools', array(), array(), '', false, false
        );
        $list->expects($this->once())
            ->method('getListSynchronization')
            ->will($this->returnValue($this->getMock('Horde_Kolab_Storage_List_Synchronization')));
        $synchronization->synchronizeList($list);
        $synchronization->synchronizeList($list);
    }

    public function testSynchronizeDataReturn()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased();
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $this->assertNull($synchronization->synchronizeData($data));
    }

    public function testDataSynchronization()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased();
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $data->expects($this->once())
            ->method('synchronize');
        $synchronization->synchronizeData($data);
    }

    public function testDataSynchronizationInSession()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased();
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $data->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('test'));
        $synchronization->synchronizeData($data);
        $this->assertTrue(isset($_SESSION['kolab_storage']['synchronization']['data']['test']));
    }

    public function testDuplicateDataSynchronization()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased();
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $data->expects($this->once())
            ->method('synchronize');
        $synchronization->synchronizeData($data);
        $synchronization->synchronizeData($data);
    }

    public function testDuplicateDataSynchronizationAfterSyncTimeElapsed()
    {
        $this->markTestSkipped('Test with embedded "sleep()". Only useful for the development phase.');
        $synchronization = new Horde_Kolab_Storage_Synchronization_TimeBased(1, 0);
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $data->expects($this->exactly(2))
            ->method('synchronize');
        $synchronization->synchronizeData($data);
        sleep(2);
        $synchronization->synchronizeData($data);
    }

}
