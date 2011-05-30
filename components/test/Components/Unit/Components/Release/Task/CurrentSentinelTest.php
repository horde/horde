<?php
/**
 * Test the current sentinel release task.
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
 * Test the current sentinel release task.
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
class Components_Unit_Components_Release_Task_CurrentSentinelTest
extends Components_TestCase
{
    public function testRunTaskWithoutCommit()
    {
        $tmp_dir = $this->_prepareApplicationDirectory();
        $tasks = $this->getReleaseTasks();
        $package = $this->_getPackage();
        $package->expects($this->any())
            ->method('getComponentDirectory')
            ->will($this->returnValue($tmp_dir));
        $package->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('4.0.1rc1'));
        $tasks->run(array('CurrentSentinel'), $package);
        $this->assertEquals(
            '----------
v4.0.1-RC1
----------
TEST',
            file_get_contents($tmp_dir . '/docs/CHANGES')
        );
        $this->assertEquals(
            'class Application {
public $version = \'4.0.1-RC1\';
}
',
            file_get_contents($tmp_dir . '/lib/Application.php')
        );
    }

    public function testRunTaskWithoutCommitOnBundle()
    {
        $tmp_dir = $this->_prepareApplicationDirectory(true);
        $tasks = $this->getReleaseTasks();
        $package = $this->_getPackage();
        $package->expects($this->any())
            ->method('getComponentDirectory')
            ->will($this->returnValue($tmp_dir));
        $package->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('4.0.1rc1'));
        $tasks->run(array('CurrentSentinel'), $package);
        $this->assertEquals(
            '----------
v4.0.1-RC1
----------
TEST',
            file_get_contents($tmp_dir . '/docs/CHANGES')
        );
        $this->assertEquals(
            'class Horde_Bundle {
const VERSION = \'4.0.1-RC1\';
}
',
            file_get_contents($tmp_dir . '/lib/Bundle.php')
        );
    }

    public function testPretend()
    {
        $tmp_dir = $this->_prepareApplicationDirectory();
        $tasks = $this->getReleaseTasks();
        $package = $this->_getPackage();
        $package->expects($this->any())
            ->method('getComponentDirectory')
            ->will($this->returnValue($tmp_dir));
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Horde'));
        $package->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('4.0.1rc1'));
        $tasks->run(array('CurrentSentinel', 'CommitPreRelease'), $package, array('pretend' => true));
        $this->assertEquals(
            array(
                sprintf('Would replace %s/docs/CHANGES with "4.0.1-RC1" now.', $tmp_dir),
                sprintf('Would replace %s/lib/Application.php with "4.0.1-RC1" now.', $tmp_dir),
                sprintf('Would run "git add %s/docs/CHANGES" now.', $tmp_dir),
                sprintf('Would run "git add %s/lib/Application.php" now.', $tmp_dir),
                'Would run "git commit -m "Released Horde-4.0.1rc1"" now.'
            ),
            $this->output->getOutput()
        );
    }

    public function testPretendOnBundle()
    {
        $tmp_dir = $this->_prepareApplicationDirectory(true);
        $tasks = $this->getReleaseTasks();
        $package = $this->_getPackage();
        $package->expects($this->any())
            ->method('getComponentDirectory')
            ->will($this->returnValue($tmp_dir));
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Horde'));
        $package->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('4.0.1rc1'));
        $tasks->run(array('CurrentSentinel', 'CommitPreRelease'), $package, array('pretend' => true));
        $this->assertEquals(
            array(
                sprintf('Would replace %s/docs/CHANGES with "4.0.1-RC1" now.', $tmp_dir),
                sprintf('Would replace %s/lib/Bundle.php with "4.0.1-RC1" now.', $tmp_dir),
                sprintf('Would run "git add %s/docs/CHANGES" now.', $tmp_dir),
                sprintf('Would run "git add %s/lib/Bundle.php" now.', $tmp_dir),
                'Would run "git commit -m "Released Horde-4.0.1rc1"" now.'
            ),
            $this->output->getOutput()
        );
    }

    private function _getPackage()
    {
        $package = $this->getMock('Components_Pear_Package', array(), array(), '', false, false);
        return $package;
    }

    private function _prepareApplicationDirectory($bundle = false)
    {
        $tmp_dir = $this->getTemporaryDirectory();
        mkdir($tmp_dir . '/docs');
        file_put_contents($tmp_dir . '/docs/CHANGES', "---\nOLD\n---\nTEST");
        mkdir($tmp_dir . '/lib');
        if ($bundle) {
            file_put_contents($tmp_dir . '/lib/Bundle.php', "class Horde_Bundle {\nconst VERSION = '0.0.0';\n}\n");
        } else {
            file_put_contents($tmp_dir . '/lib/Application.php', "class Application {\npublic \$version = '0.0.0';\n}\n");
        }
        return $tmp_dir;
    }
}