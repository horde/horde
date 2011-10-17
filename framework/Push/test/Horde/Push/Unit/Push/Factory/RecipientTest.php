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
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the recipient factory.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
                'blogger' => array(
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Push_Recipient_Blogger',
            $recipients[0]
        );
    }
}
