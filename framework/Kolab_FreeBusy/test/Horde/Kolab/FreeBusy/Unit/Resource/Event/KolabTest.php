<?php
/**
 * Test the Kolab resource handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the Kolab resource handler.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Resource_Event_KolabTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Needs to be adapted to the newer Kolab_Storage API');
    }

    public function testMethodGetrelevanceHasResultStringTheRelevanceSettingOfThisResource()
    {
        $folder = $this->_getFolder();
        $folder->expects($this->once())
            ->method('getKolabAttribute')
            ->with('incidences-for')
            ->will($this->returnValue('admins'));
        $resource = new Horde_Kolab_FreeBusy_Resource_Event_Kolab($folder);
        $this->assertEquals('admins', $resource->getRelevance());
    }

    public function testMethodGetattributeaclHasResultArrayTheResourcePermissions()
    {
        $folder = $this->_getFolder();
        $folder->expects($this->once())
            ->method('getXfbaccess')
            ->will($this->returnValue(array('a' => 'a')));
        $resource = new Horde_Kolab_FreeBusy_Resource_Event_Kolab($folder);
        $this->assertEquals(array('a' => 'a'), $resource->getAttributeAcl());
    }

    public function testMethodListeventsHasResultArrayEmptyIfThereAreNoEventsInTheGivenTimeSpan()
    {
        $objects = array();
        $resource = $this->_getData($objects);
        $start = new Horde_Date();
        $end = new Horde_Date();
        $this->assertEquals(array(), $resource->listEvents($start, $end));
    }

    public function testMethodListeventsHasResultArrayTheEventsInTheGivenTimeSpan()
    {
        $rec_start = new Horde_Date('2009-12-12 10:00:00');
        $rec_end   = new Horde_Date('2009-12-12 14:00:00');

        $objects = array(
            array(
                'uid' => 1,
                'sensitivity' => 'public',
                'start-date' => $rec_start->timestamp(),
                'end-date'   => $rec_end->timestamp(),
                'recurrence' => array(
                    'interval' => 1,
                    'cycle'    => 'daily',
                    'range-type' => 'none'
                )
            )
        );
        $resource = $this->_getData($objects);
        $start = new Horde_Date('2009-12-13 0:00:00');
        $end = new Horde_Date('2009-12-14 0:00:00');
        $result = $resource->listEvents($start, $end);
        $this->assertInstanceOf('Horde_Kolab_FreeBusy_Object_Event', $result[0]);
    }

    private function _getFolder()
    {
        $folder = $this->getMock('Horde_Kolab_Storage_Folder_Base');
        $folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        return $folder;
    }

    private function _getData(array $objects)
    {
        $data = $this->getMock(
            'Horde_Kolab_Storage_Data', array(), array(), '', false, false
        );
        $data->expects($this->once())
            ->method('getObjects')
            ->will($this->returnValue($objects));
        $folder = $this->_getFolder();
        $folder->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $resource = new Horde_Kolab_FreeBusy_Resource_Event_Kolab($folder);
        return $resource;
    }
}