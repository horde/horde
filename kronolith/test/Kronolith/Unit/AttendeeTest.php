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
        $this->_testAttendees($attendees, true);
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

    public function testId()
    {
        $attendees = $this->_getAttendees();
        $this->assertEquals('email:juergen@example.com', $attendees[0]->id);
        $this->assertEquals('name:Jane Doe', $attendees[1]->id);
        $this->assertEquals('email:jack@example.com', $attendees[2]->id);
        $this->assertEquals('email:jenny@example.com', $attendees[3]->id);
        $this->assertEquals('user:username', $attendees[4]->id);
        $this->assertEquals('user:username2', $attendees[5]->id);
    }

    public function testMatch()
    {
        $attendee = $this->_getAttendees()[0];
        $this->assertTrue($attendee->matchesEmail('juergen@example.com', false));
        $this->assertTrue($attendee->matchesEmail('Juergen@example.com', false));
        $this->assertFalse($attendee->matchesEmail('jane@example.com', false));
        $this->assertTrue($attendee->matchesEmail('juergen@example.com', true));
        $this->assertFalse($attendee->matchesEmail('Juergen@example.com', true));
        $this->assertFalse($attendee->matchesEmail('jane@example.com', true));
    }

    public function testToString()
    {
        $attendees = $this->_getAttendees();
        $this->assertEquals(
            'Jürgen Doe <juergen@example.com>',
            strval($attendees[0])
        );
        $this->assertEquals(
            'Jane Doe',
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
        $this->assertEquals(
            'User Name (username)',
            strval($attendees[4])
        );
        $this->assertEquals(
            'Another User (username2)',
            strval($attendees[5])
        );
    }

    public function testToJson()
    {
        $attendees = $this->_getAttendees();
        $this->assertEquals(
            (object)array(
                'a' => Kronolith::PART_REQUIRED,
                'e' => 'juergen@example.com',
                'l' => 'Jürgen Doe',
                'r' => Kronolith::RESPONSE_NONE,
                'u' => null,
            ),
            $attendees[0]->toJson()
        );
        $this->assertEquals(
            (object)array(
                'a' => Kronolith::PART_OPTIONAL,
                'e' => null,
                'l' => 'Jane Doe',
                'r' => Kronolith::RESPONSE_ACCEPTED,
                'u' => null,
            ),
            $attendees[1]->toJson()
        );
        $this->assertEquals(
            (object)array(
                'a' => Kronolith::PART_NONE,
                'e' => 'jenny@example.com',
                'l' => 'jenny@example.com',
                'r' => Kronolith::RESPONSE_TENTATIVE,
                'u' => null,
            ),
            $attendees[3]->toJson()
        );
        $this->assertEquals(
            (object)array(
                'a' => Kronolith::PART_NONE,
                'e' => null,
                'l' => 'User Name',
                'r' => Kronolith::RESPONSE_TENTATIVE,
                'u' => 'username',
            ),
            $attendees[4]->toJson()
        );
        $this->assertEquals(
            (object)array(
                'a' => Kronolith::PART_NONE,
                'e' => null,
                'l' => 'Another User',
                'r' => Kronolith::RESPONSE_TENTATIVE,
                'u' => 'username2',
            ),
            $attendees[5]->toJson()
        );
    }

    public function testSerialize()
    {
        $attendees = $this->_getAttendees();
        $serialized = array(serialize($attendees[0]), serialize($attendees[4]));
        $this->assertEquals(
            'C:18:"Kronolith_Attendee":102:{a:5:{s:1:"u";N;s:1:"e";s:19:"juergen@example.com";s:1:"p";i:1;s:1:"r";i:1;s:1:"n";s:11:"Jürgen Doe";}}',
            $serialized[0]
        );
        $this->assertEquals(
            'C:18:"Kronolith_Attendee":87:{a:5:{s:1:"u";s:8:"username";s:1:"e";N;s:1:"p";i:3;s:1:"r";i:4;s:1:"n";s:9:"User Name";}}',
            $serialized[1]
        );
        return $serialized;
    }

    /**
     * @depends testSerialize
     */
    public function testUnserialize($serialized)
    {
        $attendee = unserialize($serialized[0]);
        $this->assertInstanceOf('Kronolith_Attendee', $attendee);
        $this->assertNull($attendee->user);
        $this->assertEquals('juergen@example.com', $attendee->email);
        $this->assertEquals(Kronolith::PART_REQUIRED, $attendee->role);
        $this->assertEquals(Kronolith::RESPONSE_NONE, $attendee->response);
        $this->assertEquals('Jürgen Doe', $attendee->name);
        $attendee = unserialize($serialized[1]);
        $this->assertInstanceOf('Kronolith_Attendee', $attendee);
        $this->assertEquals('username', $attendee->user);
        $this->assertNull($attendee->email);
        $this->assertEquals(Kronolith::PART_NONE, $attendee->role);
        $this->assertEquals(Kronolith::RESPONSE_TENTATIVE, $attendee->response);
        $this->assertEquals('User Name', $attendee->name);
    }

    protected function _testAttendees($attendees, $smallSet = false)
    {
        $this->assertNull($attendees[0]->user);
        $this->assertEquals('juergen@example.com', $attendees[0]->email);
        $this->assertEquals(Kronolith::PART_REQUIRED, $attendees[0]->role);
        $this->assertEquals(Kronolith::RESPONSE_NONE, $attendees[0]->response);
        $this->assertEquals('Jürgen Doe', $attendees[0]->name);

        $this->assertNull($attendees[1]->user);
        $this->assertNull($attendees[1]->email);
        $this->assertEquals(Kronolith::PART_OPTIONAL, $attendees[1]->role);
        $this->assertEquals(Kronolith::RESPONSE_ACCEPTED, $attendees[1]->response);
        $this->assertEquals('Jane Doe', $attendees[1]->name);

        $this->assertEquals('jenny@example.com', $attendees[3]->email);
        $this->assertEquals(Kronolith::PART_NONE, $attendees[3]->role);
        $this->assertEquals(Kronolith::RESPONSE_TENTATIVE, $attendees[3]->response);
        $this->assertNull($attendees[3]->name);

        if (!$smallSet) {
            $this->assertEquals('username', $attendees[4]->user);
            $this->assertNull($attendees[4]->email);
            $this->assertEquals('User Name', $attendees[4]->name);

            $this->assertEquals('username2', $attendees[5]->user);
            $this->assertNull($attendees[5]->email);
            $this->assertEquals('Another User', $attendees[5]->name);
        }
    }

    protected function _getAttendees()
    {
        return include __DIR__ . '/../fixtures/attendees.php';
    }
}
