<?php
/**
 * Test the twitter recipient.
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
 * Test the twitter recipient.
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
class Horde_Push_Unit_Push_Recipient_TwitterTest
extends Horde_Push_TestCase
{
    public function testTwitterRecipient()
    {
        $push = new Horde_Push();
        $stub = new Horde_Push_Stub_Twitter();
        $push->addRecipient(new Horde_Push_Recipient_Twitter($stub))
            ->setSummary('TWEET')
            ->push();
        $this->assertEquals(
            array(
                array(
                    'method' => 'update',
                    'args'   => array('TWEET')
                )
            ),
            $stub->calls);
    }

    public function testTwitterReturn()
    {
        $push = new Horde_Push();
        $stub = new Horde_Push_Stub_Twitter();
        $result = $push->addRecipient(new Horde_Push_Recipient_Twitter($stub))
            ->setSummary('TWEET')
            ->push();
        $this->assertEquals(
            array('Pushed tweet to twitter.'),
            $result
        );
    }

    public function testTwitterPretend()
    {
        $push = new Horde_Push();
        $stub = new Horde_Push_Stub_Twitter();
        $result = $push->addRecipient(new Horde_Push_Recipient_Twitter($stub))
            ->setSummary('TWEET')
            ->push(array('pretend' => true));
        $this->assertEquals(
            array('Would push tweet "TWEET" to twitter.'),
            $result
        );
    }
}
