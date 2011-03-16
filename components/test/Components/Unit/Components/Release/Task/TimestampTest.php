<?php
/**
 * Test the timestamp release task.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the timestamp release task.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_Unit_Components_Release_Task_TimestampTest
extends Components_TestCase
{
    public function testValidateSucceeds()
    {
        $package = $this->getMock('Components_Pear_Package', array(), array(), '', false, false);
        $package->expects($this->once())
            ->method('getPackageXml')
            ->will(
                $this->returnValue(
                    dirname(__FILE__) . '/../../../../fixture/simple/package.xml'
                )
            );
        $task = $this->getReleaseTask('timestamp', $package);
        $this->assertEquals(array(), $task->validate());
    }

    public function testValidateFails()
    {
        $package = $this->getMock('Components_Pear_Package', array(), array(), '', false, false);
        $task = $this->getReleaseTask('timestamp', $package);
        $this->assertFalse($task->validate() === array());
    }

    public function testRunTaskWithoutCommit()
    {
    }

    public function testPretend()
    {
    }
}