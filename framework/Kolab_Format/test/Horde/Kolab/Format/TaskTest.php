<?php
/**
 * Test task handling within the Kolab format implementation.
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/TaskTest.php,v 1.1.2.1 2009/04/02 20:14:52 wrobel Exp $
 *
 * @package Kolab_Format
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test task handling.
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/TaskTest.php,v 1.1.2.1 2009/04/02 20:14:52 wrobel Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Format
 */
class Horde_Kolab_Format_TaskTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test basic task handling
     */
    public function testBasicTask()
    {
        $xml = Horde_Kolab_Format::factory('XML', 'task');

        // Load XML
        $task = file_get_contents(dirname(__FILE__) . '/fixtures/task.xml');
        $result = $xml->load($task);
        // Check that the xml loads fine
        $this->assertEquals($result['body'], 'TEST');
    }
}
