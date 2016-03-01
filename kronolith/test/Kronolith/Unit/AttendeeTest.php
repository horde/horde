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
 * Testing the Kronolith_Attendee class.
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GPLv2
 */
class Kronolith_Unit_AttendeeTest extends Horde_Test_Case
{
    public function testConstructor()
    {
        $this->_testAttendees($this->_getAttendees());
    }

    public function testMigrate()
    {
        $attendees = array(
            Kronolith_Attendee::migrate(
                'juergen@example.com',
                array(
                    'attendance' => Kronolith::PART_REQUIRED,
                    'response' => Kronolith::RESPONSE_NONE,
                    'name' => 'Jürgen Doe'
                )
            ),
            Kronolith_Attendee::migrate(
                'Jane Doe',
                array(
                    'attendance' => Kronolith::PART_OPTIONAL,
                    'response' => Kronolith::RESPONSE_ACCEPTED,
                    'name' => 'Jane Doe'
                )
            ),
            Kronolith_Attendee::migrate(
                'jack@example.com',
                array(
                    'attendance' => Kronolith::PART_NONE,
                    'response' => Kronolith::RESPONSE_DECLINED,
                    'name' => 'Jack Doe'
                )
            ),
            Kronolith_Attendee::migrate(
                'jenny@example.com',
                array(
                    'attendance' => Kronolith::PART_NONE,
                    'response' => Kronolith::RESPONSE_TENTATIVE
                )
            ),
        );
        $this->_testAttendees($attendees);
    }

    public function testAddressObject()
    {
        $address = $this->_getAttendees()[0]->addressObject;
        $this->assertInstanceOf('Horde_Mail_Rfc822_Address', $address);
        $this->assertEquals('juergen@example.com', $address->bare_address);
        $this->assertEquals('example.com', $address->host);
        $this->assertEquals('Jürgen Doe', $address->personal);
        $this->assertEquals('Jürgen Doe <juergen@example.com>', strval($address));
    }

    public function testDisplayName()
    {
        $attendees = $this->_getAttendees();
        $this->assertEquals('Jürgen Doe', $attendees[0]->displayName);
        $this->assertEquals('jenny@example.com', $attendees[3]->displayName);
    }

    public function testMatch()
    {
        $attendee = $this->_getAttendees()[0];
        $this->assertTrue($attendee->match('juergen@example.com', false));
        $this->assertTrue($attendee->match('Juergen@example.com', false));
        $this->assertFalse($attendee->match('jane@example.com', false));
        $this->assertTrue($attendee->match('juergen@example.com', true));
        $this->assertFalse($attendee->match('Juergen@example.com', true));
        $this->assertFalse($attendee->match('jane@example.com', true));
    }

    public function testToString()
    {
        $attendees = $this->_getAttendees();
        $this->assertEquals(
            'Jürgen Doe <juergen@example.com>',
            strval($attendees[0])
        );
        $this->assertEquals(
            '"Jane Doe"',
            strval($attendees[1])
        );
        $this->assertEquals(
            'Jack Doe <jack@example.com>',
            strval($attendees[2])
        );
        $this->assertEquals(
            'jenny@example.com',
            strval($attendees[3])
        );
    }

    public function testToJson()
    {
        $attendees = $this->_getAttendees();
        $this->assertEquals(
            (object)array(
                'a' => Kronolith::PART_REQUIRED,
                'e' => 'juergen@example.com',
                'r' => Kronolith::RESPONSE_NONE,
                'l' => 'Jürgen Doe <juergen@example.com>',
            ),
            $attendees[0]->toJson()
        );
        $this->assertEquals(
            (object)array(
                'a' => Kronolith::PART_OPTIONAL,
                'e' => 'Jane Doe',
                'r' => Kronolith::RESPONSE_ACCEPTED,
                'l' => '"Jane Doe"',
            ),
            $attendees[1]->toJson()
        );
        $this->assertEquals(
            (object)array(
                'a' => Kronolith::PART_NONE,
                'e' => 'jenny@example.com',
                'r' => Kronolith::RESPONSE_TENTATIVE,
                'l' => 'jenny@example.com',
            ),
            $attendees[3]->toJson()
        );
    }

    public function testSerialize()
    {
        $attendee = $this->_getAttendees()[0];
        $serialized = serialize($attendee);
        $this->assertEquals(
            'C:18:"Kronolith_Attendee":92:{a:4:{s:1:"e";s:19:"juergen@example.com";s:1:"p";i:1;s:1:"r";i:1;s:1:"n";s:11:"Jürgen Doe";}}',
            $serialized
        );
        return $serialized;
    }

    /**
     * @depends testSerialize
     */
    public function testUnserialize($serialized)
    {
        $attendee = unserialize($serialized);
        $this->assertInstanceOf('Kronolith_Attendee', $attendee);
        $this->assertEquals('juergen@example.com', $attendee->email);
        $this->assertEquals(Kronolith::PART_REQUIRED, $attendee->role);
        $this->assertEquals(Kronolith::RESPONSE_NONE, $attendee->response);
        $this->assertEquals('Jürgen Doe', $attendee->name);
    }

    protected function _testAttendees($attendees)
    {
        $this->assertEquals('juergen@example.com', $attendees[0]->email);
        $this->assertEquals(Kronolith::PART_REQUIRED, $attendees[0]->role);
        $this->assertEquals(Kronolith::RESPONSE_NONE, $attendees[0]->response);
        $this->assertEquals('Jürgen Doe', $attendees[0]->name);

        $this->assertEquals('Jane Doe', $attendees[1]->email);
        $this->assertEquals(Kronolith::PART_OPTIONAL, $attendees[1]->role);
        $this->assertEquals(Kronolith::RESPONSE_ACCEPTED, $attendees[1]->response);
        $this->assertEquals('Jane Doe', $attendees[1]->name);

        $this->assertEquals('jenny@example.com', $attendees[3]->email);
        $this->assertEquals(Kronolith::PART_NONE, $attendees[3]->role);
        $this->assertEquals(Kronolith::RESPONSE_TENTATIVE, $attendees[3]->response);
        $this->assertNull($attendees[3]->name);
    }

    protected function _getAttendees()
    {
        return include __DIR__ . '/../fixtures/attendees.php';
    }
}
