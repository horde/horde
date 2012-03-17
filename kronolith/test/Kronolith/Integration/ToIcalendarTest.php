<?php
/**
 * Test exporting iCalendar events.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test exporting iCalendar events.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Kronolith_Integration_ToIcalendarTest extends Kronolith_TestCase
{
    public function setUp()
    {
        $GLOBALS['registry'] = new Kronolith_Stub_Registry();
        $GLOBALS['injector'] = new Horde_Injector(new Horde_Injector_TopLevel());
        $GLOBALS['conf']['prefs']['driver'] = 'Null';
        $GLOBALS['injector']->bindFactory('Kronolith_Geo', 'Kronolith_Factory_Geo', 'create');
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Null());
        $GLOBALS['injector']->setInstance('Horde_Log_Logger', $logger);
        $GLOBALS['conf']['calendar']['driver'] = 'Mock';
    }

    public function tearDown()
    {
        unset($GLOBALS['registry']);
        unset($GLOBALS['injector']);
        unset($GLOBALS['conf']);
    }

    public function testBasicVersion1()
    {
        $this->markTestIncomplete('Fails because of quoting and recurrence exceptions.');
        $this->_testExport($this->_getEvent(), '1.0', 'export1.ics');
    }

    public function testBasicVersion2()
    {
        $this->markTestIncomplete('Fails because of recurrence exceptions.');
        $this->_testExport($this->_getEvent(), '2.0', 'export2.ics');
    }

    public function testPrivateVersion1()
    {
        $this->markTestIncomplete('Fails because of recurrence exceptions.');
        $this->_testExport($this->_getPrivateEvent(), '1.0', 'export3.ics');
    }

    public function testPrivateVersion2()
    {
        $this->markTestIncomplete('Fails because of recurrence exceptions.');
        $this->_testExport($this->_getPrivateEvent(), '2.0', 'export4.ics');
    }

    private function _testExport($event, $export_version, $fixture)
    {
        $ical = new Horde_Icalendar($export_version);
        $cal = $event->toiCalendar($ical);
        $ical->addComponent($cal);
        $this->assertEquals(
            $this->_removeDateStamp($this->_getFixture($fixture)),
            $this->_removeDateStamp($ical->exportvCalendar())
        );
    }

    private function _removeDateStamp($ics)
    {
        return preg_replace(
            '/DTSTAMP:\d\d\d\d\d\d\d\dT\d\d\d\d\d\dZ/',
            'DTSTAMP:--------T------Z',
            $ics
        );
    }

    private function _getEvent()
    {
        $event = new Kronolith_Event_Sql(new Kronolith_Stub_Driver(''));
        $event->start = new Horde_Date('2007-03-15 13:10:20');
        $event->end = new Horde_Date('2007-03-15 14:20:00');
        $event->creator = 'joe';
        $event->uid = '20070315143732.4wlenqz3edq8@horde.org';
        $event->title = 'Hübscher Termin';
        $event->description = "Schöne Bescherung\nNew line";
        $event->location = 'Allgäu';
        $event->alarm = 10;
        $event->tags = array('Schöngeistiges');
        $event->recurrence = new Horde_Date_Recurrence($event->start);
        $event->recurrence->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $event->recurrence->setRecurInterval(2);
        $event->recurrence->addException(2007, 3, 19);
        $event->initialized = true;
        return $event;
    }

    private function _getPrivateEvent()
    {
        $event = $this->_getEvent();
        $event->private = true;
        $event->status = Kronolith::STATUS_TENTATIVE;
        $event->recurrence = new Horde_Date_Recurrence($event->start);
        $event->recurrence->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
        $event->recurrence->setRecurInterval(1);
        $event->recurrence->addException(2007, 4, 15);
        $event->recurrence->addException(2007, 6, 15);
        $event->attendees =
            array('juergen@example.com' =>
                  array('attendance' => Kronolith::PART_REQUIRED,
                        'response' => Kronolith::RESPONSE_NONE,
                        'name' => 'Jürgen Doe'),
                  0 =>
                  array('attendance' => Kronolith::PART_OPTIONAL,
                        'response' => Kronolith::RESPONSE_ACCEPTED,
                        'name' => 'Jane Doe'),
                  'jack@example.com' =>
                  array('attendance' => Kronolith::PART_NONE,
                        'response' => Kronolith::RESPONSE_DECLINED,
                        'name' => 'Jack Doe'),
                  'jenny@example.com' =>
                  array('attendance' => Kronolith::PART_NONE,
                        'response' => Kronolith::RESPONSE_TENTATIVE));
        return $event;
    }

    private function _getFixture($name)
    {
        return file_get_contents(__DIR__ . '/../fixtures/' . $name);
    }
}
