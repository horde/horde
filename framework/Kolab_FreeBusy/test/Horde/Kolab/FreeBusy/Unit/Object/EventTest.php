<?php
/**
 * Test the event object.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the event object.
 *
 * Copyright 2011 Kolab Systems AG
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
class Horde_Kolab_FreeBusy_Unit_Object_EventTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function setUp()
    {
        date_default_timezone_set('UTC');
    }

    public function testDuration()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
            )
        );
        $this->assertEquals(11, $event->duration());
    }

    public function testRecurs()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
                'recurrence' => array(
                    'interval' => 1,
                    'cycle' => 'daily',
                    'range-type' => 'number',
                    'range' => 4
                )
            )
        );
        $this->assertTrue($event->recurs());
    }

    public function testGetEncodedInformationEmpty()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
            )
        );
        $this->assertEquals(
            array(
                'X-UID'      => '',
                'X-SUMMARY'  => '',
                'X-LOCATION' => '',
            ),
            $event->getEncodedInformation()
        );
    }

    public function testGetEncodedInformationComplete()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
                'uid' => 'test',
                'summary' => 'SUMMARY',
                'location' => 'TEST',
            )
        );
        $this->assertEquals(
            array(
                'X-UID'      => 'dGVzdA==',
                'X-LOCATION' => 'VEVTVA==',
                'X-SUMMARY'  => 'U1VNTUFSWQ==',
            ),
            $event->getEncodedInformation()
        );
    }

    public function testGetEncodedInformationPrivate()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
                'uid' => 'test',
                'summary' => 'SUMMARY',
                'location' => 'TEST',
                'sensitivity' => 'private',
            )
        );
        $this->assertEquals(
            array(
                'X-UID'      => 'dGVzdA==',
                'X-LOCATION' => '',
                'X-SUMMARY'  => '',
            ),
            $event->getEncodedInformation()
        );
    }

    public function testEmptyStatus()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
            )
        );
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Object_Event::STATUS_NONE,
            $event->getStatus()
        );
    }

    public function testOutOfOfficeStatus()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
                'show-time-as' => 'outofoffice',
            )
        );
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Object_Event::STATUS_OUTOFOFFICE,
            $event->getStatus()
        );
    }

    public function testBusyTimeBeforeStart()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
                'show-time-as' => 'outofoffice',
            )
        );
        $this->assertEquals(
            array(),
            $event->getBusyTimes(
                new Horde_Date('2011-11-12T11:11:11Z'),
                new Horde_Date('2011-11-12T11:11:12Z')
            )
        );
    }

    public function testBusyTimeAfterEnd()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
                'show-time-as' => 'outofoffice',
            )
        );
        $this->assertEquals(
            array(),
            $event->getBusyTimes(
                new Horde_Date('2011-11-10T11:11:11Z'),
                new Horde_Date('2011-11-10T11:11:12Z')
            )
        );
    }

    public function testBusyTimeWithin()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
                'show-time-as' => 'outofoffice',
            )
        );
        $this->assertEquals(
            array(1321009860),
            $event->getBusyTimes(
                new Horde_Date('2011-11-10T11:11:11Z'),
                new Horde_Date('2011-11-12T11:11:12Z')
            )
        );
    }

    public function testBusyTimeRecurs()
    {
        $event = new Horde_Kolab_FreeBusy_Object_Event(
            array(
                'start-date' => new Horde_Date('2011-11-11T11:11:00Z'),
                'end-date' => new Horde_Date('2011-11-11T11:11:11Z'),
                'show-time-as' => 'outofoffice',
                'recurrence' => array(
                    'interval' => 1,
                    'cycle' => 'daily',
                    'range-type' => 'number',
                    'range' => 4
                )
            )
        );
        $this->assertEquals(
            array(1321009860, 1321096260),
            $event->getBusyTimes(
                new Horde_Date('2011-11-10T11:10:59Z'),
                new Horde_Date('2011-11-12T11:11:12Z')
            )
        );
    }

}