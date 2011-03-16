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
    public function setUp()
    {
        $this->_fixture = dirname(__FILE__) . '/../../../../fixture/simple/package.xml';
    }

    public function testValidateSucceeds()
    {
        $package = $this->_getValidPackage();
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
        $tasks = $this->getReleaseTasks();
        $package = $this->_getValidPackage();
        $package->expects($this->once())
            ->method('timestamp');
        $tasks->run(array('timestamp'), $package);
    }

    public function testPretend()
    {
        $tasks = $this->getReleaseTasks();
        $package = $this->_getValidPackage();
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('NAME'));
        $package->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.0'));
        $tasks->run(array('Timestamp', 'CommitPreRelease'), $package, array('pretend' => true));
        $this->assertEquals(
            array(
                sprintf('Would timestamp %s now.', $this->_fixture),
                sprintf('Would run "git add %s" now.', $this->_fixture),
                'Would run "git commit -m "Released NAME-1.0.0"" now.'
            ),
            $this->output->getOutput()
        );
    }

    private function _getValidPackage()
    {
        $package = $this->getMock('Components_Pear_Package', array(), array(), '', false, false);
        $package->expects($this->any())
            ->method('getPackageXml')
            ->will($this->returnValue($this->_fixture));
        return $package;
    }
}