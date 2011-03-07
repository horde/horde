<?php
/**
 * Test resource handling within the Kolab filter implementation.
 *
 * @package Kolab_Filter
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Filter.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Resource.php';
require_once 'Horde/Kolab/Filter/Incoming.php';
require_once 'Horde/Icalendar.php';
require_once 'Horde/Icalendar/Vfreebusy.php';

/**
 * Test resource handling
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Filter
 */
class Horde_Kolab_Filter_ResourceTest extends Horde_Kolab_Test_Filter
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        $result = $this->prepareBasicSetup();

        $this->server  = &$result['server'];
        $this->storage = &$result['storage'];
        $this->auth    = &$result['auth'];

        global $conf;

        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;
        $conf['kolab']['imap']['allow_special_users'] = true;
        $conf['kolab']['filter']['reject_forged_from_header'] = false;
        $conf['kolab']['filter']['email_domain'] = 'example.org';
        $conf['kolab']['filter']['privileged_networks'] = '127.0.0.1,192.168.0.0/16';
        $conf['kolab']['filter']['verify_from_header'] = true;
        $conf['kolab']['filter']['calendar_id'] = 'calendar';
        $conf['kolab']['filter']['calendar_pass'] = 'calendar';
        $conf['kolab']['filter']['lmtp_host'] = 'imap.example.org';
        $conf['kolab']['filter']['simple_locks'] = true;
        $conf['kolab']['filter']['simple_locks_timeout'] = 3;

        $conf['kolab']['filter']['itipreply']['driver'] = 'echo';
        $conf['kolab']['filter']['itipreply']['params']['host'] = 'localhsot';
        $conf['kolab']['filter']['itipreply']['params']['port'] = 25;

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getNewFolder();
        $folder->setName('Kalender');
        $result = $folder->save(array('type' => 'event',
                                      'default' => true));
        $this->assertNoError($result);
    }

    /**
     * Test retrieval of the resource information
     */
    public function testGetResourceData()
    {
        $r = &new Kolab_Resource();
        $d = $r->_getResourceData('test@example.org', 'wrobel@example.org');
        $this->assertNoError($d);
        $this->assertEquals('wrobel@example.org', $d['id']);
        $this->assertEquals('home.example.org', $d['homeserver']);
        $this->assertEquals('ACT_REJECT_IF_CONFLICTS', $d['action']);
        $this->assertEquals('cn=Gunnar Wrobel', $d['cn']);
    }

    /**
     * Test manual actions
     */
    public function testManual()
    {
        $r = &new Kolab_Resource();
        $this->assertTrue($r->handleMessage('otherhost', 'test@example.org', 'wrobel@example.org', null));
        $r = &new Kolab_Resource();
        $this->assertTrue($r->handleMessage('localhost', 'test@example.org', 'wrobel@example.org', null));
    }


    /**
     * Test invitation.
     */
    public function testRecurrenceInvitation()
    {
        $this->markTestIncomplete('Fails for unknown reason.');

        $GLOBALS['KOLAB_FILTER_TESTING'] = new Horde_Icalendar_Vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_Icalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_Icalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/recur_invitation.eml',
                           dirname(__FILE__) . '/fixtures/recur_invitation.ret2',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $this->assertEquals(1222419600, $events[0]['start-date']);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test an that contains a long string.
     */
    public function testLongStringInvitation()
    {
        $this->markTestIncomplete('Fails for unknown reason.');

        require_once 'Horde/Icalendar/Vfreebusy.php';
        $GLOBALS['KOLAB_FILTER_TESTING'] = new Horde_Icalendar_Vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_Icalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_Icalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/longstring_invitation.eml',
                           dirname(__FILE__) . '/fixtures/longstring_invitation.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $summaries = array();
        foreach ($events as $event) {
            $summaries[] = $event['summary'];
        }
        $this->assertContains('invitationtest2', $summaries);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test an invitation that books a whole day.
     */
    public function testWholeDayInvitation()
    {
        require_once 'Horde/Icalendar/Vfreebusy.php';
        $GLOBALS['KOLAB_FILTER_TESTING'] = new Horde_Icalendar_Vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_Icalendar::_parseDateTime('20090401T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_Icalendar::_parseDateTime('20090601T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/invitation_whole_day.eml',
                           dirname(__FILE__) . '/fixtures/invitation_whole_day.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $summaries = array();
        foreach ($events as $event) {
            $summaries[] = $event['summary'];
        }
        $this->assertContains('issue3558', $summaries);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test an invitation with plus addressing.
     */
    public function testInvitationWithPlusAddressing()
    {
        require_once 'Horde/Icalendar/Vfreebusy.php';
        $GLOBALS['KOLAB_FILTER_TESTING'] = new Horde_Icalendar_Vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_Icalendar::_parseDateTime('20090401T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_Icalendar::_parseDateTime('20090601T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/invitation_plus_addressing.eml',
                           dirname(__FILE__) . '/fixtures/invitation_plus_addressing.ret',
                           '', '', 'test@example.org', 'wrobel+laptop@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $summaries = array();
        foreach ($events as $event) {
            $summaries[] = $event['summary'];
        }
        $this->assertContains('issue3521', $summaries);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test invitation when no default has been given.
     */
    public function testRecurrenceNodefault()
    {
        $GLOBALS['KOLAB_FILTER_TESTING'] = new Horde_Icalendar_Vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_Icalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_Icalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/recur_invitation.eml',
                           dirname(__FILE__) . '/fixtures/recur_invitation.ret',
                           '', '', 'wrobel@example.org', 'else@example.org', 
                           'home.example.org', $params);
    }

    /**
     * Test an issue with recurring invitations.
     *
     * https://issues.kolab.org/issue3868
     */
    public function testIssue3868()
    {
        $this->markTestIncomplete('Fails for unknown reason.');

        $GLOBALS['KOLAB_FILTER_TESTING'] = new Horde_Icalendar_Vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_Icalendar::_parseDateTime('20090901T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_Icalendar::_parseDateTime('20091101T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/recur_invitation2.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $this->assertEquals(1251950400, $events[0]['start-date']);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test all day events
     */
    public function testAllDay()
    {
        $GLOBALS['KOLAB_FILTER_TESTING'] = new Horde_Icalendar_Vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_Icalendar::_parseDateTime('20090901T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_Icalendar::_parseDateTime('20091101T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/allday_invitation.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();

        $this->assertEquals(1251928800, $events[0]['start-date']);
        $this->assertEquals(1252015200, $events[0]['end-date']);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test that the attendee status gets transferred.
     */
    public function testAttendeeStatusInvitation()
    {
        $this->markTestIncomplete('Sends mail');

        require_once 'Horde/Icalendar/Vfreebusy.php';
        $GLOBALS['KOLAB_FILTER_TESTING'] = new Horde_Icalendar_Vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_Icalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_Icalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/attendee_status_invitation.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $summaries = array();
        foreach ($events as $event) {
            foreach ($event['attendee'] as $attendee) {
                switch ($attendee['smtp-address']) {
                case 'needs@example.org':
                    $this->assertEquals('none', $attendee['status']);
                    break;
                case 'accepted@example.org':
                    $this->assertEquals('accepted', $attendee['status']);
                    break;
                case 'declined@example.org':
                    $this->assertEquals('declined', $attendee['status']);
                    break;
                case 'tentative@example.org':
                    $this->assertEquals('tentative', $attendee['status']);
                    break;
                case 'delegated@example.org':
                    $this->assertEquals('none', $attendee['status']);
                    break;
                default:
                    $this->fail('Unexpected attendee!');
                    break;
                }
            }
        }
        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

}
