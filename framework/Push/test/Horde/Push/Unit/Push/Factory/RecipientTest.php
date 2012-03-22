<?php
/**
 * Test the recipient factory.
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
 * Test the recipient factory.
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
class Horde_Push_Unit_Push_Factory_RecipientTest
extends Horde_Push_TestCase
{
    public function testTwitter()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('twitter'),
                'twitter' => array(
                    'key' => 'test',
                    'secret' => 'test',
                    'token_key' => 'test',
                    'token_secret' => 'test'
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Push_Recipient_Twitter',
            $recipients[0]
        );
    }

    public function testBlogger()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('blogger'),
                'blogger' => array(),
                'http' => array(
                    'proxy' => array(
                        'proxy_host' => 'localhost',
                        'proxy_port' => 8080,
                        'proxy_user' => 'user',
                        'proxy_pass' => 'pass',
                    )
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Push_Recipient_Blogger',
            $recipients[0]
        );
    }

    public function testMail()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('mail'),
                'mailer' => array(
                    'type' => 'mock',
                    'from' => 'user@example.org'
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Push_Recipient_Mail',
            $recipients[0]
        );
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testUnknownTransport()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('mail'),
                'mailer' => array(
                    'type' => 'UNKNOWN',
                    'from' => 'user@example.org'
                )
            )
        );
    }

    public function testMultipleConfigRecipients()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('blogger', 'blogger'),
                'blogger' => array(),
            )
        );
        $this->assertEquals(2, count($recipients));
    }

    public function testMultipleCommandLineRecipients()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array('recipients' => 'blogger,blogger'),
            array('blogger' => array())
        );
        $this->assertEquals(2, count($recipients));
    }

    public function testTrimmedRecipient()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array('recipients' => " blogger , blogger\n"),
            array('blogger' => array())
        );
        $this->assertEquals(2, count($recipients));
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testUnknownRecipient()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array('recipients' => array('UNKNOWN'))
        );
    }

    public function testNamedRecipient()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('personal-blog'),
                'recipient' => array(
                    'personal-blog' => array(
                        'type' => 'blogger',
                        'blogger' => array()
                    )
                ),
            )
        );
        $this->assertInstanceOf(
            'Horde_Push_Recipient_Blogger',
            $recipients[0]
        );
    }

    public function testFromHeader()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('mail-me'),
                'recipient' => array(
                    'mail-me' => array(
                        'type' => 'mail',
                        'mailer' => array(
                            'type' => 'mock',
                            'from' => 'from@example.com'
                        )
                    )
                ),
            )
        );
        $push = new Horde_Push();
        $push->setSummary('E-MAIL');
        foreach ($recipients as $recipient) {
            $push->addRecipient($recipient);
        }
        $result = $push->push(array('pretend' => true));
        $this->assertContains('from: from@example.com', $result[0]);
    }

    public function testToHeader()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('mail-me:recipient@example.com'),
                'recipient' => array(
                    'mail-me' => array(
                        'type' => 'mail',
                        'mailer' => array(
                            'type' => 'mock',
                            'from' => 'from@example.com'
                        )
                    )
                ),
            )
        );
        $push = new Horde_Push();
        $push->setSummary('E-MAIL');
        foreach ($recipients as $recipient) {
            $push->addRecipient($recipient);
        }
        $result = $push->push(array('pretend' => true));
        $this->assertContains('to: recipient@example.com', $result[0]);
    }

    public function testToConfiguredHeader()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $recipients = $factory->create(
            array(),
            array(
                'recipients' => array('mail-me'),
                'recipient' => array(
                    'mail-me' => array(
                        'type' => 'mail',
                        'acl' => 'recipient@example.com',
                        'mailer' => array(
                            'type' => 'mock',
                            'from' => 'from@example.com',
                        )
                    )
                ),
            )
        );
        $push = new Horde_Push();
        $push->setSummary('E-MAIL');
        foreach ($recipients as $recipient) {
            $push->addRecipient($recipient);
        }
        $result = $push->push(array('pretend' => true));
        $this->assertContains('to: recipient@example.com', $result[0]);
    }

    public function testEmpty()
    {
        $factory = new Horde_Push_Factory_Recipients();
        $this->assertEquals(array(), $factory->create(array(),array()));
    }
}
