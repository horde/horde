<?php
/**
 * Test handling all day events.
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
 * Test handling all day events.
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
class Kronolith_Integration_AllDayTest extends Kronolith_TestCase
{
    public function testAllDay()
    {
        $ical_event = $this->_getFixture(0);
        $driver = new Kronolith_Stub_Driver();
        $event = new Kronolith_Event_Sql($driver);
        $event->fromiCalendar($ical_event);
        $this->assertTrue($event->isAllDay());
    }

    public function testAllDayWithoutEnd()
    {
        $ical_event = $this->_getFixture(1);
        $driver = new Kronolith_Stub_Driver();
        $event = new Kronolith_Event_Sql($driver);
        $event->fromiCalendar($ical_event);
        $this->assertTrue($event->isAllDay());
    }

    public function testOneHour()
    {
        $ical_event = $this->_getFixture(2);
        $driver = new Kronolith_Stub_Driver();
        $event = new Kronolith_Event_Sql($driver);
        $event->fromiCalendar($ical_event);
        $this->assertFalse($event->isAllDay());
    }

    private function _getFixture($element)
    {
        $iCal = new Horde_Icalendar();
        $iCal->parsevCalendar(
            file_get_contents(__DIR__ . '/../fixtures/allday.ics')
        );
        $components = $iCal->getComponents();
        return $components[$element];
    }
}
