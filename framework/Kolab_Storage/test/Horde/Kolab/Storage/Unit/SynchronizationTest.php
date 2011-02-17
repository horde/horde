<?php
/**
 * Test the synchronization handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the synchronization handler.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_SynchronizationTest
extends Horde_Kolab_Storage_TestCase
{
    public function testSynchronizeListReturn()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization();
        $list = $this->getMock(
            'Horde_Kolab_Storage_List_Base', array(), array(), '', false, false
        );
        $this->assertNull($synchronization->synchronizeList($list));
    }

    public function testListSynchronization()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization();
        $list = $this->getMock(
            'Horde_Kolab_Storage_List_Base', array(), array(), '', false, false
        );
        $list->expects($this->once())
            ->method('synchronize');
        $synchronization->synchronizeList($list);
    }

    public function testListSynchronizationInSession()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization();
        $list = $this->getMock(
            'Horde_Kolab_Storage_List_Base', array(), array(), '', false, false
        );
        $list->expects($this->once())
            ->method('getConnectionId')
            ->will($this->returnValue('test'));
        $synchronization->synchronizeList($list);
        $this->assertTrue($_SESSION['kolab_storage']['synchronization']['list']['test']);
    }

    public function testDuplicateListSynchronization()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization();
        $list = $this->getMock(
            'Horde_Kolab_Storage_List_Base', array(), array(), '', false, false
        );
        $list->expects($this->once())
            ->method('synchronize');
        $synchronization->synchronizeList($list);
        $synchronization->synchronizeList($list);
    }

    public function testSynchronizeDataReturn()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization();
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $this->assertNull($synchronization->synchronizeData($data));
    }

    public function testDataSynchronization()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization();
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $data->expects($this->once())
            ->method('synchronize');
        $synchronization->synchronizeData($data);
    }

    public function testDataSynchronizationInSession()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization();
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $data->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('test'));
        $synchronization->synchronizeData($data);
        $this->assertTrue($_SESSION['kolab_storage']['synchronization']['data']['test']);
    }

    public function testDuplicateDataSynchronization()
    {
        $synchronization = new Horde_Kolab_Storage_Synchronization();
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data_Base', array(), array(), '', false, false
        );
        $data->expects($this->once())
            ->method('synchronize');
        $synchronization->synchronizeData($data);
        $synchronization->synchronizeData($data);
    }

}
