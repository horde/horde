<?php
/**
 * Test importing iCalendar events.
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
 * Test importing iCalendar events.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
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
class Kronolith_Integration_FromIcalendarTest extends Kronolith_TestCase
{
    public function testStart()
    {
        $event = $this->_getFixture('fromicalendar.ics');
        $this->assertEquals('2010-11-01 10:00:00', (string)$event->start);
    }

    public function testEnd()
    {
        $event = $this->_getFixture('fromicalendar.ics');
        $this->assertEquals('2010-11-01 11:00:00', (string)$event->end);
    }

    public function testAllDay()
    {
        $this->assertFalse(
            $this->_getFixture('fromicalendar.ics')->isAllDay()
        );
    }

    public function testRrule20()
    {
        $event = $this->_getFixture('fromicalendar.ics');
        $this->assertEquals(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;UNTIL=20101129T090000Z',
            $event->recurrence->toRrule20(new Horde_Icalendar())
        );
    }

    public function testExceptions()
    {
        $event = $this->_getFixture('fromicalendar.ics');
        $this->assertEquals(
            array('20101108', '20101122'),
            $event->recurrence->exceptions
        );
    }

    public function testExceptionsTwo()
    {
        $GLOBALS['conf']['calendar']['driver'] = 'Mock';
        $GLOBALS['injector'] = new Horde_Injector(new Horde_Injector_TopLevel());
        $event = $this->_getFixture('bug7068.ics');
        $this->assertEquals(
            array('20080729'),
            $event->recurrence->exceptions
        );

        unset($GLOBALS['injector']);
        unset($GLOBALS['conf']);
    }

    public function testExceptionsThree()
    {
        $GLOBALS['conf']['calendar']['driver'] = 'Mock';
        $GLOBALS['injector'] = new Horde_Injector(new Horde_Injector_TopLevel());
        $event = $this->_getFixture('bug7068.ics', 1);
        $this->assertEquals(
            array ('20080722', '20080729'),
            $event->recurrence->exceptions
        );

        unset($GLOBALS['injector']);
        unset($GLOBALS['conf']);
    }

    public function testInvalidTimezone()
    {
        $GLOBALS['conf']['calendar']['driver'] = 'Mock';
        $GLOBALS['injector'] = new Horde_Injector(new Horde_Injector_TopLevel());
        $event = $this->_getFixture('bug11688.ics', 1);
        $event->start->toDateTime();

        unset($GLOBALS['injector']);
        unset($GLOBALS['conf']);
    }

    public function testAttendees()
    {
        foreach (array(1, 2) as $i) {
            $event = $this->_getFixture("attendees$i.ics");
            $this->assertInstanceOf(
                'Kronolith_Attendee_List',
                $event->attendees
            );
            $this->assertEquals(2, count($event->attendees));
            $this->assertArrayHasKey('user:username', $event->attendees);
            $this->assertArrayHasKey('user:username2', $event->attendees);
            $this->assertInstanceOf(
                'Kronolith_Attendee',
                $event->attendees['user:username']
            );
            $this->assertEquals(
                'username',
                $event->attendees['user:username']->user
            );
            $this->assertEquals(
                Kronolith::PART_NONE,
                $event->attendees['user:username']->role
            );
            $this->assertEquals(
                Kronolith::RESPONSE_TENTATIVE,
                $event->attendees['user:username']->response
            );
            $this->assertEquals(
                'username2',
                $event->attendees['user:username2']->user
            );
            $this->assertEquals(
                Kronolith::PART_OPTIONAL,
                $event->attendees['user:username2']->role
            );
            $this->assertEquals(
                Kronolith::RESPONSE_NONE,
                $event->attendees['user:username2']->response
            );
        }
    }

    private function _getFixture($name, $item = 0)
    {
        $iCal = new Horde_Icalendar();
        $iCal->parsevCalendar(
            file_get_contents(__DIR__ . '/../fixtures/' . $name)
        );
        $components = $iCal->getComponents();
        $event = new Kronolith_Event_Sql(new Kronolith_Stub_Driver());
        $event->fromiCalendar($components[$item], true);
        return $event;
    }
}
