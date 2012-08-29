<?php
/**
 * Tests the synchronisation log decorator.
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
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Tests the synchronisation log decorator.
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
class Horde_Kolab_Storage_Unit_List_Synchronization_Decorator_LogTest
extends PHPUnit_Framework_TestCase
{
    public function testRegisterListener()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Synchronization');
        $base->expects($this->once())
            ->method('registerListener');
        $synchronization = new Horde_Kolab_Storage_List_Synchronization_Decorator_Log(
            $base, $this->getMock('Horde_Log_Logger')
        );
        $listener = $this->getMock('Horde_Kolab_Storage_List_Synchronization_Listener');
        $synchronization->registerListener($listener);
    }

    public function testSynchronize()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Synchronization');
        $base->expects($this->once())
            ->method('synchronize');
        $synchronization = new Horde_Kolab_Storage_List_Synchronization_Decorator_Log(
            $base, $this->getMock('Horde_Log_Logger')
        );
        $synchronization->synchronize();
    }

    public function testSynchronizationLog()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Synchronization');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $logger->expects($this->once())
            ->method('debug')
            ->with('Synchronized the Kolab folder list!');
        $synchronization = new Horde_Kolab_Storage_List_Synchronization_Decorator_Log(
            $base, $logger
        );
        $synchronization->synchronize();
    }
}