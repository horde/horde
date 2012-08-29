<?php
/**
 * Test the mail recipient.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Push
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Push
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the mail recipient.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Push
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Push
 */
class Horde_Push_Unit_Push_Recipient_MailTest
extends Horde_Push_TestCase
{
    public function testMailSubject()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('test@example.com'), 'from@example.com');
        $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->push();
        $this->assertEquals(
            'E-MAIL',
            $mx->sentMessages[0]['headers']['subject']
        );
    }

    public function testMailBody()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('test@example.com'), 'from@example.com');
        $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->addContent('BODY')
            ->push();
        $this->assertEquals("BODY\n", $mx->sentMessages[0]['body']);
    }

    public function testMailHtmlBody()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('test@example.com'), 'from@example.com');
        $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->addContent('<b>BODY</b>', 'text/html')
            ->push();
        $this->assertContains('<b>BODY</b>', $mx->sentMessages[0]['body']);
        $this->assertContains('This message is in MIME format', $mx->sentMessages[0]['body']);
    }

    public function testMailHtmlAndPlainBody()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('test@example.com'), 'from@example.com');
        $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->addContent('PLAIN', 'text/plain')
            ->addContent('HTML', 'text/html')
            ->push();
        $this->assertContains('PLAIN', $mx->sentMessages[0]['body']);
        $this->assertContains('HTML', $mx->sentMessages[0]['body']);
        $this->assertContains('This message is in MIME format', $mx->sentMessages[0]['body']);
    }

    public function testImage()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('test@example.com'), 'from@example.com');
        $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->addContent('JPG', 'image/jpeg')
            ->push();
        $this->assertContains('SlBH', $mx->sentMessages[0]['body']);
        $this->assertContains('image/jpeg', $mx->sentMessages[0]['body']);
    }

    public function testFromHeader()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('from' => 'from@example.com'));
        $recipient->setAcl('test@example.com');
        $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->push();
        $this->assertEquals(
            'from@example.com',
            $mx->sentMessages[0]['headers']['from']
        );
    }

    public function testToHeader()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('from' => 'from@example.com'));
        $recipient->setAcl('test@example.com');
        $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->push();
        $this->assertEquals(
            'test@example.com',
            $mx->sentMessages[0]['headers']['to']
        );
    }

    public function testReturn()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('from' => 'from@example.com'));
        $recipient->setAcl('test@example.com');
        $return = $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->push();
        $this->assertEquals(array('Pushed mail to test@example.com.'), $return);
    }

    public function testPretend()
    {
        $push = new Horde_Push();
        $mx = new Horde_Mail_Transport_Mock();
        $recipient = new Horde_Push_Recipient_Mail($mx, array('from' => 'from@example.com'));
        $recipient->setAcl('test@example.com');
        $return = $push->addRecipient($recipient)
            ->setSummary('E-MAIL')
            ->push(array('pretend' => true));
        $this->assertContains('Would push mail', $return[0]);
    }
}
