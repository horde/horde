<?php
/**
 * Test the Horde_Push interface.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Horde_Push interface.
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
class Horde_Push_Unit_PushTest
extends Horde_Push_TestCase
{
    public function testSummary()
    {
        $push = new Horde_Push();
        $this->assertEquals('', $push->getSummary());
    }

    public function testSetSummary()
    {
        $push = new Horde_Push();
        $push->setSummary('SUMMARY');
        $this->assertEquals('SUMMARY', $push->getSummary());
    }

    public function testFluidSummary()
    {
        $push = new Horde_Push();
        $this->assertInstanceOf('Horde_Push', $push->setSummary('SUMMARY'));
    }

    public function testContent()
    {
        $push = new Horde_Push();
        $this->assertEquals(array(), $push->getContent());
    }

    public function testAddContent()
    {
        $push = new Horde_Push();
        $push->addContent('CONTENT');
        $this->assertEquals(
            array(
                array(
                    'content' => 'CONTENT',
                    'params' => array(),
                )
            ),
            $push->getContent()
        );
    }

    public function testFluidAddContent()
    {
        $push = new Horde_Push();
        $this->assertInstanceOf('Horde_Push', $push->addContent('CONTENT'));
    }

    public function testMockRecipient()
    {
        $push = new Horde_Push();
        $mock = new Horde_Push_Recipient_Mock();
        $push->addRecipient($mock)
            ->addContent('CONTENT')
            ->push();
        $this->assertInstanceOf('Horde_Push', $mock->pushed[0]);
    }

    public function testFluidAddRecipient()
    {
        $push = new Horde_Push();
        $mock = new Horde_Push_Recipient_Mock();
        $this->assertInstanceOf('Horde_Push', $push->addRecipient($mock));
    }

}
