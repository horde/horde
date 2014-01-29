<?php
/**
 * Test task handling within the Kolab format implementation.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Test task handling.
 *
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Integration_TaskTest
extends Horde_Kolab_Format_TestCase
{

    /**
     * Test basic task handling
     */
    public function testBasicTask()
    {
        $xml = $this->getFactory()->create('XML', 'task');

        // Load XML
        $task = file_get_contents(__DIR__ . '/../fixtures/task.xml');
        $result = $xml->load($task);
        // Check that the xml loads fine
        $this->assertEquals($result['body'], 'TEST');
    }
}
