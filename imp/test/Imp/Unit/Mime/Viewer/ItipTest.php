<?php
/**
 * Test the itip response handling.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the itip response handling.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */
class Imp_Unit_Mime_Viewer_ItipTest
extends PHPUnit_Framework_TestCase
{
    private $_contents;
    private $_contentsCharset;
    private $_contentsData;
    private $_contentsFactory;
    private $_identity;
    private $_identityId = 'default';
    private $_mail;
    private $_mailbox;
    private $_notifyStack = array();
    private $_oldtz;

    public function setUp()
    {
        $this->_oldtz = date_default_timezone_get();
        date_default_timezone_set('UTC');


        $injector = $this->getMock('Horde_Injector', array(), array(), '', false);
        $injector->expects($this->any())
            ->method('getInstance')
            ->will($this->returnCallback(array($this, '_injectorGetInstance')));
        $GLOBALS['injector'] = $injector;


        $registry = $this->getMock('Horde_Registry', array(), array(), '', false);
        $registry->expects($this->any())
            ->method('getCharset')
            ->will($this->returnValue('UTF-8'));
        $GLOBALS['registry'] = $registry;

        $notification = $this->getMock('Horde_Notification_Handler', array(), array(), '', false);
        $notification->expects($this->any())
            ->method('push')
            ->will($this->returnCallback(array($this, '_notificationHandler')));
        $GLOBALS['notification'] = $notification;

        $GLOBALS['conf']['server']['name'] = 'localhost';
        $_REQUEST['identity'] = 'test';
        $_SERVER['REMOTE_ADDR'] = 'localhost';
    }

    public function tearDown()
    {
        date_default_timezone_set($this->_oldtz);
    }

    public function _injectorGetInstance($interface)
    {
        switch ($interface) {
        case 'IMP_Contents':
            if (!isset($this->_contents)) {
                $contents= $this->getMock('IMP_Contents', array(), array(), '', false);
                $contents->expects($this->any())
                    ->method('getMIMEPart')
                    ->will($this->returnCallback(array($this, '_getMimePart')));
                $this->_contents = $contents;
            }
            return $this->_contents;

        case 'IMP_Factory_Contents':
            if (!isset($this->_contentsFactory)) {
                $cf = $this->getMock('IMP_Factory_Contents', array(), array(), '', false);
                $cf->expects($this->any())
                    ->method('create')
                    ->will($this->returnValue($this->_injectorGetInstance('IMP_Contents')));
                $this->_contentsFactory = $cf;
            }
            return $this->_contentsFactory;

        case 'IMP_Factory_Mailbox':
            if (!isset($this->_mailbox)) {
                $mbox = $this->getMock('IMP_Factory_Mailbox', array(), array(), '', false);
                $mbox->expects($this->any())
                    ->method('create')
                    ->will($this->returnValue(new IMP_Mailbox('foo')));
                $this->_mailbox = $mbox;
            }
            return $this->_mailbox;

        case 'IMP_Identity':
            if (!isset($this->_identity)) {
                $identity = $this->getMock('Horde_Core_Prefs_Identity', array(), array(), '', false);
                $identity->expects($this->any())
                    ->method('setDefault')
                    ->will($this->returnCallback(array($this, '_identitySetDefault')));
                $identity->expects($this->any())
                    ->method('getDefault')
                    ->will($this->returnCallback(array($this, '_identityGetDefault')));
                $identity->expects($this->any())
                    ->method('getFromAddress')
                    ->will($this->returnValue(new Horde_Mail_Rfc822_Address('test@example.org')));
                $identity->expects($this->any())
                    ->method('getValue')
                    ->will($this->returnCallback(array($this, '_identityGetValue')));
                $identity->expects($this->any())
                    ->method('getMatchingIdentity')
                    ->will($this->returnCallback(array($this, '_identityGetMatchingIdentity')));
                $this->_identity = $identity;
            }
            return $this->_identity;

        case 'IMP_Mail':
            if (!isset($this->_mail)) {
                $this->_mail = new Horde_Mail_Transport_Mock();
            }
            return $this->_mail;
        }
    }

    public function _getMimePart($id)
    {
        $part = new Horde_Mime_Part();
        $part->setContents($this->_contentsData);
        return $part;
    }

    public function _identityGetMatchingIdentity($mail)
    {
        if ($mail == 'test@example.org') {
            return 'test';
        }
    }

    public function _identitySetDefault($id)
    {
        if (($id != 'test') &&
            ($id != 'other') &&
            ($id != 'default')) {
            throw new Exception("Unexpected default $id!");
        }

        $this->_identityId = $id;
    }

    public function _identityGetDefault()
    {
        return $this->_identityId;
    }

    public function _identityGetValue($value)
    {
        switch ($value) {
        case 'fullname':
            return 'Mr. Test';

        case 'replyto_addr':
            switch ($this->_identityId) {
            case 'test':
                return 'test@example.org';

            case 'other':
                return 'reply@example.org';
            }
        }
    }

    public function _notificationHandler($msg, $code)
    {
        $this->_notifyStack = array($msg, $code);
    }

    public function _prefsGetValue($pref)
    {
        switch ($pref) {
        case 'date_format':
            return '%x';

        case 'twentyFour':
            return true;
        }
    }

    /* Begin tests */

    public function testAcceptingAnInvitationResultsInReplySent()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertContains('Reply Sent.', reset($this->_notifyStack));
    }

    /**
     * @todo This seems strange. How should the user know that an incomplete
     * event results in no action but just redisplays the invitation?
     */
    public function testAcceptingAnInvitationWithoutOrganizerResultsInNoAction()
    {
        $this->markTestSkipped('This test fails because the vCal does not contain a DURATION attribute. Exception is thrown from Framework, so not pertinent to check in IMP.');

        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('UID', '1');

        $this->_doImple('accept', $inv->exportvCalendar());

        $mail = $this->_getMail();
        $this->assertEquals('', $mail);
    }

    public function testAcceptingAnInvitationResultsInMimeMessageSent()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertInstanceOf('Horde_Icalendar', $this->_getIcalendar());
    }

    public function testResultMessageContainsProductId()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('-//The Horde Project//Horde Application Framework 4//EN', $this->_getIcalendar()->getAttribute('PRODID'));
    }

    public function testResultMessageIndicatesMethodReply()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('REPLY', $this->_getIcalendar()->getAttribute('METHOD'));
    }

    public function testResultMessageContainsVevent()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertInstanceOf('Horde_Icalendar_Vevent', $this->_getVevent());
    }

    public function testResultMessageContainsCopiedUid()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('1001', $this->_getVevent()->getAttribute('UID'));
    }

    /**
     * @todo Should this really throw an exception? Adapt once the Mime Viewer
     * does error handling (empty array return value)
     */
    public function testResultMessageThrowsExceptionIfUidIsMissing()
    {
        try {
            $this->_doImple('accept', "BEGIN:VEVENT\nORGANIZER:somebody@example.com\nDTSTAMP:20100816T143648Z\nDTSTART:20100816T143648Z\nEND:VEVENT");
            $this->fail('Expecting Exception.');
        } catch (Exception $e) {}
    }

    public function testResultMessageContainsCopiedSummary()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('Test Invitation', $this->_getVevent()->getAttribute('SUMMARY'));
    }

    public function testResultMessageContainsEmptySummaryIfNotAvailable()
    {
        $this->_doImple('accept', $this->_getMinimalInvitation()->exportvCalendar());
        $this->assertEquals('', $this->_getVevent()->getAttribute('SUMMARY'));
    }

    public function testResultMessageContainsCopiedDescription()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('You are invited', $this->_getVevent()->getAttribute('DESCRIPTION'));
    }

    public function testResultMessageContainsEmptyDescriptionIfNotAvailable()
    {
        $this->_doImple('accept', $this->_getMinimalInvitation()->exportvCalendar());
        $this->assertEquals('Default', $this->_getVevent()->getAttributeDefault('DESCRIPTION', 'Default'));
    }

    public function testResultMessageContainsCopiedStartDate()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('1222426800', $this->_getVevent()->getAttribute('DTSTART'));
    }

    public function testResultMessageContainsCopiedStartDateParameters()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $dtstart = $this->_getVevent()->getAttribute('DTSTART', true);
        $this->assertEquals(array('TEST' => 'start'), array_pop($dtstart));
    }

    public function testResultMessageContainsCopiedEndDate()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('1222430400', $this->_getVevent()->getAttribute('DTEND'));
    }

    public function testResultMessageContainsCopiedEndDateParameters()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $dtend = $this->_getVevent()->getAttribute('DTEND', true);
        $this->assertEquals(array('TEST' => 'end'), array_pop($dtend));
    }

    public function testResultMessageContainsCopiedDurationIfEndDateIsMissing()
    {
        $start = new Horde_Date('20080926T110000');
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('UID', '1001');
        $inv->setAttribute('ORGANIZER', 'orga@example.org');
        $inv->setAttribute('DTSTART', $start->timestamp());
        $inv->setAttribute('DURATION', '3600', array('TEST' => 'duration'));

        $this->_doImple('accept', $inv->exportvCalendar());
        $this->assertEquals('3600', $this->_getVevent()->getAttribute('DURATION'));
    }

    public function testResultMessageContainsCopiedDurationParametersIfEndDateIsMissing()
    {
        $start = new Horde_Date('20080926T110000');
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('UID', '1001');
        $inv->setAttribute('ORGANIZER', 'orga@example.org');
        $inv->setAttribute('DTSTART', $start->timestamp());
        $inv->setAttribute('DURATION', '3600', array('TEST' => 'duration'));

        $this->_doImple('accept', $inv->exportvCalendar());

        $duration = $this->_getVevent()->getAttribute('DURATION', true);
        $this->assertEquals(array('TEST' => 'duration'), array_pop($duration));
    }

    public function testResultMessageContainsCopiedInvitation()
    {
        $inv = $this->_getInvitation();
        $inv->setAttribute('SEQUENCE', '10');

        $this->_doImple('accept', $inv->exportvCalendar());
        $this->assertEquals('10', $this->_getVevent()->getAttribute('SEQUENCE'));
    }

    public function testResultMessageContainsNoSequenceIfNotAvailable()
    {
        $this->_doImple('accept', $this->_getMinimalInvitation()->exportvCalendar());
        $this->assertEquals('99', $this->_getVevent()->getAttributeDefault('SEQUENCE', '99'));
    }

    public function testResultMessageContainsCopiedOrganizer()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('mailto:orga@example.org', $this->_getVevent()->getAttribute('ORGANIZER'));
    }

    public function testResultMessageContainsCopiedOrganizerParameters()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $organizer = $this->_getVevent()->getAttribute('ORGANIZER', true);
        $this->assertEquals(array('CN' => 'Mr. Orga'), array_pop($organizer));
    }

    public function testResultMessageContainsAttendeeEmail()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('mailto:test@example.org', $this->_getVevent()->getAttribute('ATTENDEE'));
    }

    public function testResultMessageContainsAttendeeName()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $attendee = $this->_getVevent()->getAttribute('ATTENDEE', true);
        $params = array_pop($attendee);
        $this->assertEquals('Mr. Test', $params['CN']);
    }

    public function testAcceptActionResultsInMessageWithAttendeeStatusAccept()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $attendee = $this->_getVevent()->getAttribute('ATTENDEE', true);
        $params = array_pop($attendee);
        $this->assertEquals('ACCEPTED', $params['PARTSTAT']);
    }

    public function testDenyActionResultsInMessageWithAttendeeStatusDecline()
    {
        $this->_doImple('deny', $this->_getInvitation()->exportvCalendar());
        $attendee = $this->_getVevent()->getAttribute('ATTENDEE', true);
        $params = array_pop($attendee);
        $this->assertEquals('DECLINED', $params['PARTSTAT']);
    }

    public function testTentativeActionResultsInMessageWithAttendeeStatusTentative()
    {
        $this->_doImple('tentative', $this->_getInvitation()->exportvCalendar());
        $attendee = $this->_getVevent()->getAttribute('ATTENDEE', true);
        $params = array_pop($attendee);
        $this->assertEquals('TENTATIVE', $params['PARTSTAT']);
    }

    public function testResultIsAMultipartMimeMessage()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('multipart/alternative', $this->_getMimeMessage()->getType());
    }

    public function testAcceptResultContainsAcceptMimeMessage()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals("Mr. Test has accepted the invitation to the following event:\n\nTest Invitation", str_replace("\r", '', trim($this->_getMimeMessage()->getPart(1)->getContents())));
    }

    public function testDenyResultContainsDeclineMimeMessage()
    {
        $this->_doImple('deny', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals("Mr. Test has declined the invitation to the following event:\n\nTest Invitation", str_replace("\r", '', trim($this->_getMimeMessage()->getPart(1)->getContents())));
    }

    public function testTentativeResultContainsTentativeMimeMessage()
    {
        $this->_doImple('tentative', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals("Mr. Test has tentatively accepted the invitation to the following event:\n\nTest Invitation", str_replace("\r", '', trim($this->_getMimeMessage()->getPart(1)->getContents())));
    }

    public function testResultMimeMessagePartTwoHasFileName()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $ics = $this->_getMimeMessage()->getPart(2);
        if (!$ics) {
            $this->fail('Missing second message part!');
        }
        $this->assertEquals('event-reply.ics', $ics->getName());
    }

    public function testResultMimeMessagePartTwoHasContentTypeParameter()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $ics = $this->_getMimeMessage()->getPart(2);
        if (!$ics) {
            $this->fail('Missing second message part!');
        }
        $this->assertEquals('REPLY', $ics->getContentTypeParameter('METHOD'));
    }

    public function testResultMimeMessageHeadersContainsReceivedHeader()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertContains('(Horde Framework) with HTTP', $this->_getMailHeaders()->getValue('Received'));
    }

    public function testResultMimeMessageHeadersContainsMessageId()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertContains('.Horde.', $this->_getMailHeaders()->getValue('Message-ID'));
    }

    public function testResultMimeMessageHeadersContainsDate()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $date = $this->_getMailHeaders()->getValue('Date');
        $this->assertTrue(!empty($date));
    }

    public function testResultMimeMessageHeadersContainsFrom()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('"Mr. Test" <test@example.org>', $this->_getMailHeaders()->getValue('From'));
    }

    public function testResultMimeMessageHeadersContainsTo()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('orga@example.org', $this->_getMailHeaders()->getValue('To'));
    }

    public function testAcceptActionResultMimeMessageHeadersContainsAcceptSubject()
    {
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('Accepted: Test Invitation', $this->_getMailHeaders()->getValue('Subject'));
    }

    public function testDenyActionResultMimeMessageHeadersContainsDeclineSubject()
    {
        $this->_doImple('deny', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('Declined: Test Invitation', $this->_getMailHeaders()->getValue('Subject'));
    }

    public function testTentativeActionResultMimeMessageHeadersContainsTentativeSubject()
    {
        $this->_doImple('tentative', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('Tentative: Test Invitation', $this->_getMailHeaders()->getValue('Subject'));
    }
    public function testResultMimeMessageHeadersContainsReplyToForAlternateIdentity()
    {
        $_REQUEST['identity'] = 'other';
        $this->_doImple('accept', $this->_getInvitation()->exportvCalendar());
        $this->assertEquals('reply@example.org', $this->_getMailHeaders()->getValue('Reply-To'));
    }

    private function _getInvitation()
    {
        $start = new Horde_Date('20080926T110000');
        $end = new Horde_Date('20080926T120000');
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('UID', '1001');
        $inv->setAttribute('SUMMARY', 'Test Invitation');
        $inv->setAttribute('DESCRIPTION', 'You are invited');
        $inv->setAttribute('LOCATION', 'Somewhere');
        $inv->setAttribute('ORGANIZER', 'mailto:orga@example.org', array('cn' => 'Mr. Orga'));
        $inv->setAttribute('DTSTART', $start->timestamp(), array('TEST' => 'start'));
        $inv->setAttribute('DTEND', $end->timestamp(), array('TEST' => 'end'));
        $inv->setAttribute('ATTENDEE', 'mailto:orga@example.org', array('CN' => 'Mr. Orga'));
        $inv->setAttribute('ATTENDEE', 'mailto:test@example.org', array('CN' => 'Mr. Test'));
        return $inv;
    }

    private function _getMinimalInvitation()
    {
        $start = new Horde_Date('20080926T110000');
        $end = new Horde_Date('20080926T120000');
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('UID', '1001');
        $inv->setAttribute('ORGANIZER', 'mailto:orga@example.org', array('cn' => 'Mr. Orga'));
        $inv->setAttribute('DTSTART', $start->timestamp());
        $inv->setAttribute('DTEND', $end->timestamp());
        return $inv;
    }

    private function _getMailHeaders()
    {
        if (isset($GLOBALS['injector']->getInstance('IMP_Mail')->sentMessages[0])) {
            $headers = Horde_Mime_Headers::parseHeaders(
                $GLOBALS['injector']->getInstance('IMP_Mail')->sentMessages[0]['header_text']
            );
            if (!$headers instanceOf Horde_Mime_Headers) {
                $this->fail('Failed parsing message headers!');
                return new Horde_Mime_Headers();
            }
            return $headers;
        }
        $this->fail('No message has been sent!');
    }

    private function _getMail()
    {
        $mail = '';
        if (isset($GLOBALS['injector']->getInstance('IMP_Mail')->sentMessages[0])) {
            $mail .= $GLOBALS['injector']->getInstance('IMP_Mail')->sentMessages[0]['header_text'] .
                "\n\n" .
                $GLOBALS['injector']->getInstance('IMP_Mail')->sentMessages[0]['body'];
        }
        return $mail;
    }

    private function _getMimeMessage()
    {
        $mail = $this->_getMail();
        return Horde_Mime_Part::parseMessage($mail);
    }

    private function _getIcalendar()
    {
        $part = $this->_getMimeMessage();
        $ics = $part->getPart(2);
        if (!$ics) {
            $this->fail('Missing second message part!');
        }
        $iCal = new Horde_Icalendar();
        $iCal->parsevCalendar($ics->getContents());
        return $iCal;
    }

    private function _getVevent()
    {
        return $this->_getIcalendar()->getComponent(0);
    }

    private function _doImple($action, $data)
    {
        $vars = new Horde_Variables(array(
            'itip_action' => array($action),
            'mailbox' => 'foo',
            'mime_id' => 1,
            'uid' => 1
        ));
        $this->_contentsData = $data;

        $imple = new IMP_Ajax_Imple_ItipRequest(array());
        $imple->handle($vars);
    }

}
