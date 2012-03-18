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
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test importing iCalendar events.
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
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;UNTIL=20101129T230000Z',
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

    private function _getFixture($name, $item = 0)
    {
        $iCal = new Horde_Icalendar();
        $iCal->parsevCalendar(
            file_get_contents(__DIR__ . '/../fixtures/' . $name)
        );
        $components = $iCal->getComponents();
        $event = new Kronolith_Event_Sql(new Kronolith_Stub_Driver());
        $event->fromiCalendar($components[$item]);
        return $event;
    }
}
