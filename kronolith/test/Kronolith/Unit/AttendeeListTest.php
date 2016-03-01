<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GPLv2
 */

/**
 * Testing the Kronolith_Attendee_List class.
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GPLv2
 */
class Kronolith_Unit_AttendeeListTest extends Horde_Test_Case
{
    public function testConstructor()
    {
        new Kronolith_Attendee_List();
        new Kronolith_Attendee_List(array());
        new Kronolith_Attendee_List($this->_getAttendees());
    }

    public function testCountable()
    {
        $this->assertEquals(0, count(new Kronolith_Attendee_List()));
        $this->assertEquals(4, count($this->_getList()));
    }

    public function testIterator()
    {
        $count = 0;
        foreach ($this->_getList() as $attendee) {
            $this->assertInstanceOf('Kronolith_Attendee', $attendee);
            $count++;
        }
        $this->assertEquals(4, $count);
    }

    public function testArrayAccess()
    {
        $attendees = $this->_getList();

        // offsetExists
        $this->assertTrue(isset($attendees['jack@example.com']));

        // offsetGet
        $attendee = $attendees['jack@example.com'];
        $this->assertInstanceOf('Kronolith_Attendee', $attendee);
        $this->assertEquals('jack@example.com', $attendee->email);
        $this->assertEquals(Kronolith::PART_NONE, $attendee->role);
        $this->assertEquals(Kronolith::RESPONSE_DECLINED, $attendee->response);
        $this->assertEquals('Jack Doe', $attendee->name);

        // offsetSet
        $attendee->name = 'New Name';
        $attendees['jack@example.com'] = $attendee;
        $this->assertEquals('New Name', $attendees['jack@example.com']->name);
        $attendee = new Kronolith_Attendee(array('email' => 'foo@example.com'));
        $attendees['foo@example.com'] = $attendee;
        $this->assertEquals($attendee, $attendees['foo@example.com']);

        // offsetUnset
        unset($attendees['jack@example.com']);
        $this->assertFalse(isset($attendees['jack@example.com']));
    }

    public function testParse()
    {
        $attendees = Kronolith_Attendee_List::parse(
            'Jürgen Doe <juergen@example.com>, Jane Doe,Jack Doe <jack@example.com>,  jenny@example.com',
            $this->getMockBuilder('Horde_Notification_Handler')
                ->disableOriginalConstructor()
                ->getMock()
        );
        $this->assertInstanceOf('Kronolith_Attendee_List', $attendees);
        $this->assertEquals(4, count($attendees));
        $expectedAttendees = $this->_getAttendees();
        foreach ($expectedAttendees as &$attendee) {
            $attendee->role = Kronolith::PART_REQUIRED;
            $attendee->response = Kronolith::RESPONSE_NONE;
        }
        $this->assertEquals(
            $expectedAttendees,
            iterator_to_array($attendees)
        );
    }

    public function testAdd()
    {
        $attendees = $this->_getList();
        $attendees->add(
            new Kronolith_Attendee(array('email' => 'foo@example.com'))
        );
        $this->assertEquals(5, count($attendees));
        $this->assertTrue(isset($attendees['foo@example.com']));

        $attendees->add(new Kronolith_Attendee_List(array(
            new Kronolith_Attendee(array('email' => 'bar@example.com'))
        )));
        $this->assertEquals(6, count($attendees));
        $this->assertTrue(isset($attendees['bar@example.com']));
    }

    public function testHas()
    {
        $attendees = $this->_getList();
        $this->assertTrue($attendees->has('juergen@example.com'));
        $this->assertTrue($attendees->has('Juergen@example.com'));
        $this->assertTrue($attendees->has($attendees['juergen@example.com']));
        $this->assertFalse($attendees->has('foo@example.com'));
        $this->assertFalse(
            $attendees->has(
                new Kronolith_Attendee(array('email' => 'foo@example.com'))
            )
        );
    }

    public function testWithout()
    {
        $attendees = $this->_getList()->without(array('juergen@example.com'));
        $this->assertInstanceOf('Kronolith_Attendee_List', $attendees);
        $this->assertEquals(3, count($attendees));
    }

    public function testGetEmailList()
    {
        $attendees = $this->_getList()->getEmailList();
        $this->assertInstanceOf('Horde_Mail_Rfc822_List', $attendees);
        $this->assertEquals(4, count($attendees));
    }

    public function testToString()
    {
        $this->assertEquals(
            'Jürgen Doe <juergen@example.com>, "Jane Doe", Jack Doe <jack@example.com>, jenny@example.com',
            strval($this->_getList())
        );
    }

    protected function _getList()
    {
        return new Kronolith_Attendee_List($this->_getAttendees());
    }

    protected function _getAttendees()
    {
        return include __DIR__ . '/../fixtures/attendees.php';
    }
}
