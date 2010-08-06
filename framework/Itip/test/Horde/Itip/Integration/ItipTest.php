<?php
/**
 * Test the itip response handling.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Itip
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Itip
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the itip response handling.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category   Horde
 * @package    Itip
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Integration_ItipTest
extends PHPUnit_Framework_TestCase
{
    public function testMinimalItipHandlingSteps()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept()
        );
        $this->assertEquals($reply->getAttribute('ATTENDEE'), 'MAILTO:test@example.org');
    }

    public function testDefaultSequenceIdSetToZero()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept()
        );
        $this->assertSame($reply->getAttribute('SEQUENCE'), 0);
    }

    public function testForCopiedSequenceIdFromRequestToResponse()
    {
        $inv = $this->_getInvitation();
        $inv->setAttribute('SEQUENCE', 555);
        $iTip = $this->_getItip($inv);
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept()
        );
        $this->assertSame($reply->getAttribute('SEQUENCE'), 555);
    }

    public function testForCopiedStartTimeFromRequestToResponse()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept()
        );
        $this->assertSame($reply->getAttribute('DTSTART'), array('20080926T110000'));
    }

    public function testForCopiedEndTimeFromRequestToResponse()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept()
        );
        $this->assertSame($reply->getAttribute('DTEND'), array('20080926T120000'));
    }

    public function testForCopiedDurationFromRequestToResponse()
    {
        $vCal = new Horde_iCalendar();
        $inv = Horde_iCalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('METHOD', 'REQUEST');
        $inv->setAttribute('UID', '1');
        $inv->setAttribute('SUMMARY', 'Test Invitation');
        $inv->setAttribute('DESCRIPTION', 'You are invited');
        $inv->setAttribute('ORGANIZER', 'orga@example.org');
        $inv->setAttribute('DTSTART', '20080926T110000');
        $inv->setAttribute('DURATION', 3600);
        $iTip = $this->_getItip($inv);
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept()
        );
        $this->assertSame($reply->getAttribute('DURATION'), 3600);
    }

    public function testForCopiedOrganizerFromRequestToResponse()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept()
        );
        $this->assertSame($reply->getAttribute('ORGANIZER'), 'orga@example.org');
    }

    public function testForCopiedUidFromRequestToResponse()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept()
        );
        $this->assertSame($reply->getAttribute('UID'), '1');
    }

    public function testIcalendarResponseHasMethodReply()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getIcalendarResponse(
            new Horde_Itip_Response_Type_Accept(), ''
        );
        $this->assertEquals($reply->getAttribute('METHOD'), 'REPLY');
    }

    public function testIcalendarResponseAllowsSettingTheProductId()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getIcalendarResponse(
            new Horde_Itip_Response_Type_Accept(), 'My product'
        );
        $this->assertEquals($reply->getAttribute('PRODID'), 'My product');
    }

    public function testMessageResponseHasFromAddress()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $iTip = $this->_getItip();
        $reply = $iTip->getMessageResponse(
            new Horde_Itip_Response_Type_Accept(), '', ''
        );
        
        $this->assertContains('From: Mister Test <test@example.org>', $reply[0]->toString());
    }

    public function testMessageResponseHasToAddress()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $iTip = $this->_getItip();
        $reply = $iTip->getMessageResponse(
            new Horde_Itip_Response_Type_Accept(), '', ''
        );
        
        $this->assertContains('To: orga@example.org', $reply[0]->toString());
    }

    public function testMessageResponseHasSubjectAddress()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $iTip = $this->_getItip();
        $reply = $iTip->getMessageResponse(new Horde_Itip_Response_Type_Accept(), '');
        $this->assertContains('Subject: Accepted: Test', $reply[0]->toString());
    }

    public function testMessageResponseAllowsAddingCommentsToTheSubject()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $iTip = $this->_getItip();
        $reply = $iTip->getMessageResponse(
            new Horde_Itip_Response_Type_Accept(), '', 'info'
        );
        $this->assertContains('Subject: Accepted [info]: Test', $reply[0]->toString());
    }

    public function testAttendeeHoldsInformationAboutMailAddress()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept(), ''
        );
        $this->assertEquals($reply->getAttribute('ATTENDEE'), 'MAILTO:test@example.org');
    }

    public function testAttendeeHoldsInformationAboutCommonNameAndStatus()
    {
        $iTip = $this->_getItip();
        $reply = $iTip->getVeventResponse(
            new Horde_Itip_Response_Type_Accept(), ''
        );
        $parameters = $reply->getAttribute('ATTENDEE', true);
        $this->assertEquals(
            array_pop($parameters),
            array(
                'CN' => 'Mister Test',
                'PARTSTAT' => 'ACCEPTED'
            )
        );
    }


    /**
     * Test the basic iTip handling method.
     */
    /* public function testBasic() */
    /* { */
    /*     $iTip = new Horde_Itip( */
    /*         $request, $resource */
    /*     ); */
    /*     $reply = $itip->setResponseType($responseType) */
    /*         ->setSubjectComment('text') */
    /*         ->setMessageComment('text') */
    /*         ->setUpdate(true) */
    /*         ->getMimeMessage(); */
    /* } */

    private function _getItip($invitation = null)
    {
        if ($invitation === null) {
            $invitation = $this->_getInvitation();
        }
        return Horde_Itip::factory(
            $invitation,
            $this->_getResource()
        );
    }

    private function _getInvitation()
    {
        $vCal = new Horde_Icalendar();
        $inv = Horde_Icalendar::newComponent('VEVENT', $vCal);
        $inv->setAttribute('METHOD', 'REQUEST');
        $inv->setAttribute('UID', '1');
        $inv->setAttribute('SUMMARY', 'Test Invitation');
        $inv->setAttribute('DESCRIPTION', 'You are invited');
        $inv->setAttribute('ORGANIZER', 'orga@example.org');
        $inv->setAttribute('DTSTART', array('20080926T110000'));
        $inv->setAttribute('DTEND', array('20080926T120000'));
        return $inv;
    }

    private function _getResource($mail = null, $cn = null)
    {
        if ($mail === null) {
            $mail = 'test@example.org';
        }
        if ($cn === null) {
            $cn = 'Mister Test';
        }
        return new Horde_Itip_Resource_Base($mail, $cn);
    }
}
