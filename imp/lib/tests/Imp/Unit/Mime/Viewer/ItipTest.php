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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the itip response handling.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/gpl.html GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */
class Imp_Unit_Mime_Viewer_ItipTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $GLOBALS['registry'] = new IMP_Stub_Registry();
        $GLOBALS['browser'] = new IMP_Stub_Browser();
        $GLOBALS['prefs'] = new IMP_Stub_Prefs();
        $GLOBALS['injector'] = new IMP_Stub_Injector();
        $GLOBALS['conf']['server']['name'] = 'localhost';
        $_GET['identity'] = 'test';
        $_SERVER['REMOTE_ADDR'] = 'localhost';
        $_SESSION = array('imp' => array('view' => 'imp'));
    }

    public function testAcceptingAnInvitationResultsInReplySent()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $result = $viewer->render('inline');
        $result = array_pop($result);
        $this->assertContains('Reply Sent.', $result['data']);
    }

    /**
     * @todo This seems strange. How should the user know that an incomplete
     * event results in no action but just redisplays the invitation?
     */
    public function testAcceptingAnInvitationWithoutOrganizerResultsInNoAction()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('UID', '1');
        $viewer = $this->_getViewer($inv->exportvCalendar());
        $viewer->render('inline');
        $mail = $this->_getMail();
        $this->assertEquals('', $mail);
    }

    public function testAcceptingAnInvitationResultsInMimeMessageSent()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertType('Horde_Icalendar', $this->_getIcalendar());
    }

    public function testResultMessageContainsProductId()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('-//The Horde Project//Horde Application Framework 4//EN', $this->_getIcalendar()->getAttribute('PRODID'));
    }

    public function testResultMessageIndicatesMethodReply()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('REPLY', $this->_getIcalendar()->getAttribute('METHOD'));
    }

    public function testResultMessageContainsVevent()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertType('Horde_Icalendar_Vevent', $this->_getVevent());
    }

    public function testResultMessageContainsCopiedUid()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('1001', $this->_getVevent()->getAttribute('UID'));
    }

    /**
     * @todo Should this really throw an exception? Adapt once the Mime Viewer
     * does error handling (empty array return value)
     */
    public function testResultMessageThrowsExceptionIfUidIsMissing()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $viewer = $this->_getViewer("BEGIN:VEVENT\nORGANIZER:somebody@example.com\nDTSTAMP:20100816T143648Z\nDTSTART:20100816T143648Z\nEND:VEVENT");
        $this->assertSame(array(), $viewer->render('inline'));
    }

    public function testResultMessageContainsCopiedSummary()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('Test Invitation', $this->_getVevent()->getAttribute('SUMMARY'));
    }

    public function testResultMessageContainsEmptySummaryIfNotAvailable()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getMinimalInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('', $this->_getVevent()->getAttribute('SUMMARY'));
    }

    public function testResultMessageContainsCopiedDescription()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('You are invited', $this->_getVevent()->getAttribute('DESCRIPTION'));
    }

    public function testResultMessageContainsEmptyDescriptionIfNotAvailable()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getMinimalInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('Default', $this->_getVevent()->getAttributeDefault('DESCRIPTION', 'Default'));
    }

    public function testResultMessageContainsCopiedStartDate()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('1222419600', $this->_getVevent()->getAttribute('DTSTART'));
    }

    public function testResultMessageContainsCopiedStartDateParameters()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $dtstart = $this->_getVevent()->getAttribute('DTSTART', true);
        $this->assertEquals(array('TEST' => 'start'), array_pop($dtstart));
    }

    public function testResultMessageContainsCopiedEndDate()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('1222423200', $this->_getVevent()->getAttribute('DTEND'));
    }

    public function testResultMessageContainsCopiedEndDateParameters()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $dtend = $this->_getVevent()->getAttribute('DTEND', true);
        $this->assertEquals(array('TEST' => 'end'), array_pop($dtend));
    }

    public function testResultMessageContainsCopiedDurationIfEndDateIsMissing()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $start = new Horde_Date('20080926T110000');
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('UID', '1001');
        $inv->setAttribute('ORGANIZER', 'orga@example.org');
        $inv->setAttribute('DTSTART', $start->timestamp());
        $inv->setAttribute('DURATION', '3600', array('TEST' => 'duration'));
        $viewer = $this->_getViewer($inv->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('3600', $this->_getVevent()->getAttribute('DURATION'));
    }

    public function testResultMessageContainsCopiedDurationParametersIfEndDateIsMissing()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $start = new Horde_Date('20080926T110000');
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('METHOD', 'REQUEST');
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('UID', '1001');
        $inv->setAttribute('ORGANIZER', 'orga@example.org');
        $inv->setAttribute('DTSTART', $start->timestamp());
        $inv->setAttribute('DURATION', '3600', array('TEST' => 'duration'));
        $viewer = $this->_getViewer($inv->exportvCalendar());
        $viewer->render('inline');
        $duration = $this->_getVevent()->getAttribute('DURATION', true);
        $this->assertEquals(array('TEST' => 'duration'), array_pop($duration));
    }

    public function testResultMessageContainsCopiedInvitation()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $inv = $this->_getInvitation();
        $inv->setAttribute('SEQUENCE', '10');
        $viewer = $this->_getViewer($inv->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('10', $this->_getVevent()->getAttribute('SEQUENCE'));
    }

    public function testResultMessageContainsNoSequenceIfNotAvailable()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getMinimalInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('99', $this->_getVevent()->getAttributeDefault('SEQUENCE', '99'));
    }

    public function testResultMessageContainsCopiedOrganizer()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('mailto:orga@example.org', $this->_getVevent()->getAttribute('ORGANIZER'));
    }

    public function testResultMessageContainsCopiedOrganizerParameters()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $organizer = $this->_getVevent()->getAttribute('ORGANIZER', true);
        $this->assertEquals(array('CN' => 'Mr. Orga'), array_pop($organizer));
    }

    public function testResultMessageContainsAttendeeEmail()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('mailto:test@example.org', $this->_getVevent()->getAttribute('ATTENDEE'));
    }

    public function testResultMessageContainsAttendeeName()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $attendee = $this->_getVevent()->getAttribute('ATTENDEE', true);
        $params = array_pop($attendee);
        $this->assertEquals('Mr. Test', $params['CN']);
    }

    public function testAcceptActionResultsInMessageWithAttendeeStatusAccept()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $attendee = $this->_getVevent()->getAttribute('ATTENDEE', true);
        $params = array_pop($attendee);
        $this->assertEquals('ACCEPTED', $params['PARTSTAT']);
    }

    public function testDenyActionResultsInMessageWithAttendeeStatusDecline()
    {
        $_GET['itip_action'] = array(0 => 'deny');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $attendee = $this->_getVevent()->getAttribute('ATTENDEE', true);
        $params = array_pop($attendee);
        $this->assertEquals('DECLINED', $params['PARTSTAT']);
    }

    public function testTentativeActionResultsInMessageWithAttendeeStatusTentative()
    {
        $_GET['itip_action'] = array(0 => 'tentative');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $attendee = $this->_getVevent()->getAttribute('ATTENDEE', true);
        $params = array_pop($attendee);
        $this->assertEquals('TENTATIVE', $params['PARTSTAT']);
    }

    public function testResultIsAMultipartMimeMessage()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('multipart/alternative', $this->_getMimeMessage()->getType());
    }

    public function testAcceptResultContainsAcceptMimeMessage()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals("Mr. Test has accepted the invitation to the following event:\n\nTest Invitation", $this->_getMimeMessage()->getPart(1)->getContents());
    }

    public function testDenyResultContainsDeclineMimeMessage()
    {
        $_GET['itip_action'] = array(0 => 'deny');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals("Mr. Test has declined the invitation to the following event:\n\nTest Invitation", $this->_getMimeMessage()->getPart(1)->getContents());
    }

    public function testTentativeResultContainsTentativeMimeMessage()
    {
        $_GET['itip_action'] = array(0 => 'tentative');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals("Mr. Test has tentatively accepted the invitation to the following event:\n\nTest Invitation", $this->_getMimeMessage()->getPart(1)->getContents());
    }

    public function testResultMimeMessagePartOneHasRegistryCharset()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar(), 'BIG5');
        $viewer->render('inline');
        $this->assertEquals('BIG5', $this->_getMimeMessage()->getPart(1)->getCharset());
    }

    public function testResultMimeMessagePartTwoHasRegistryCharset()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar(), 'BIG5');
        $viewer->render('inline');
        $ics = $this->_getMimeMessage()->getPart(2);
        if (!$ics) {
            $this->fail('Missing second message part!');
        }
        $this->assertEquals('BIG5', $ics->getCharset());
    }

    public function testResultMimeMessagePartTwoHasFileName()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $ics = $this->_getMimeMessage()->getPart(2);
        if (!$ics) {
            $this->fail('Missing second message part!');
        }
        $this->assertEquals('event-reply.ics', $ics->getName());
    }

    public function testResultMimeMessagePartTwoHasContentTypeParameter()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $ics = $this->_getMimeMessage()->getPart(2);
        if (!$ics) {
            $this->fail('Missing second message part!');
        }
        $this->assertEquals('REPLY', $ics->getContentTypeParameter('METHOD'));
    }

    public function testResultMimeMessageHeadersContainsReceivedHeader()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertContains('(Horde Framework) with HTTP', $this->_getMailHeaders()->getValue('Received'));
    }

    public function testResultMimeMessageHeadersContainsMessageId()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertContains('.Horde.', $this->_getMailHeaders()->getValue('Message-ID'));
    }

    public function testResultMimeMessageHeadersContainsDate()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $date = $this->_getMailHeaders()->getValue('Date');
        $this->assertTrue(!empty($date));
    }

    public function testResultMimeMessageHeadersContainsFrom()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('"Mr. Test" <test@example.org>', $this->_getMailHeaders()->getValue('From'));
    }

    public function testResultMimeMessageHeadersContainsTo()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('orga@example.org', $this->_getMailHeaders()->getValue('To'));
    }

    public function testAcceptActionResultMimeMessageHeadersContainsAcceptSubject()
    {
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('Accepted: Test Invitation', $this->_getMailHeaders()->getValue('Subject'));
    }

    public function testDenyActionResultMimeMessageHeadersContainsDeclineSubject()
    {
        $_GET['itip_action'] = array(0 => 'deny');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('Declined: Test Invitation', $this->_getMailHeaders()->getValue('Subject'));
    }

    public function testTentativeActionResultMimeMessageHeadersContainsTentativeSubject()
    {
        $_GET['itip_action'] = array(0 => 'tentative');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('Tentative: Test Invitation', $this->_getMailHeaders()->getValue('Subject'));
    }
    public function testResultMimeMessageHeadersContainsReplyToForAlternateIdentity()
    {  
      $_GET['identity'] = 'other';
        $_GET['itip_action'] = array(0 => 'accept');
        $viewer = $this->_getViewer($this->_getInvitation()->exportvCalendar());
        $viewer->render('inline');
        $this->assertEquals('reply@example.org', $this->_getMailHeaders()->getValue('Reply-To'));
    }

    private function _getViewer($invitation, $charset = 'UTF-8')
    {
        $part = new Horde_Mime_Part();
        $part->setContents($invitation);
        return new IMP_Mime_Viewer_Itip($part, array('charset' => $charset));
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
            $mail .= $GLOBALS['injector']->getInstance('IMP_Mail')->sentMessages[0]['header_text'] . "\n\n";
            $body = $GLOBALS['injector']->getInstance('IMP_Mail')->sentMessages[0]['body'];
            while (!feof($body)) {
                $mail .= fread($body, 8192);
            }
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
}
