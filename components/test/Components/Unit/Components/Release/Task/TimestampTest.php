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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the timestamp release task.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_Unit_Components_Release_Task_TimestampTest
extends Components_TestCase
{
    public function setUp()
    {
        $this->_fixture = __DIR__ . '/../../../../fixture/simple';
    }

    public function testValidateSucceeds()
    {
        $this->markTestSkipped('Release no longer possible with outdated package.xml');
        $package = $this->getComponent($this->_fixture);
        $task = $this->getReleaseTask('timestamp', $package);
        $this->assertEquals(array(), $task->validate(array()));
    }

    public function testValidateFails()
    {
        $package = $this->getComponent($this->_fixture . '/NO_SUCH_PACKAGE');
        $task = $this->getReleaseTask('timestamp', $package);
        $this->assertFalse($task->validate(array()) === array());
    }

    public function testRunTaskWithoutCommit()
    {
        $tasks = $this->getReleaseTasks();
        $package = $this->_getValidPackage();
        $package->expects($this->once())
            ->method('timestampAndSync');
        $tasks->run(array('timestamp'), $package);
    }

    public function testPretend()
    {
        $this->markTestSkipped('Release no longer possible with outdated package.xml');
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($this->_fixture);
        $tasks->run(
            array('Timestamp', 'CommitPreRelease'),
            $package,
            array(
                'pretend' => true,
                'commit' => new Components_Helper_Commit(
                    $this->output,
                    array('pretend' => true)
                )
            )
        );
        $this->assertEquals(
            array(
                sprintf('Would timestamp "%s" now and synchronize its change log.', realpath($this->_fixture . '/package.xml')),
                sprintf('Would run "git add %s" now.', realpath($this->_fixture . '/package.xml')),
                'Would run "git commit -m "Released Fixture-0.0.1"" now.'
            ),
            $this->output->getOutput()
        );
    }

    private function _getValidPackage()
    {
        $package = $this->getMock('Components_Component', array(), array(), '', false, false);
        $package->expects($this->any())
            ->method('hasLocalPackageXml')
            ->will($this->returnValue(true));
        return $package;
    }
}