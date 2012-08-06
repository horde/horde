<?php
/**
 * Test the next sentinel release task.
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
 * Test the next sentinel release task.
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
class Components_Unit_Components_Release_Task_NextSentinelTest
extends Components_TestCase
{
    public function testRunTaskWithoutCommit()
    {
        $tmp_dir = $this->_prepareApplicationDirectory();
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($tmp_dir);
        $tasks->run(array('NextSentinel'), $package, array('next_version' => '5.0.0-git'));
        $this->assertEquals(
            '----------
v5.0.0-git
----------



---
OLD
---
TEST',
            file_get_contents($tmp_dir . '/docs/CHANGES')
        );
        $this->assertEquals(
            'class Application {
public $version = \'5.0.0-git\';
}
',
            file_get_contents($tmp_dir . '/lib/Application.php')
        );
    }

    public function testPretend()
    {
        $tmp_dir = $this->_prepareApplicationDirectory();
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($tmp_dir);
        $tasks->run(
            array('NextSentinel', 'CommitPostRelease'),
            $package,
            array(
                'next_version' => '5.0.0-git',
                'pretend' => true,
                'commit' => new Components_Helper_Commit(
                    $this->output,
                    array('pretend' => true)
                )
            )
        );
        $this->assertEquals(
            array(
                sprintf('Would extend sentinel in %s/docs/CHANGES with "5.0.0-git" now.', $tmp_dir),
                sprintf('Would replace sentinel in %s/lib/Application.php with "5.0.0-git" now.', $tmp_dir),
                sprintf('Would run "git add %s/docs/CHANGES" now.', $tmp_dir),
                sprintf('Would run "git add %s/lib/Application.php" now.', $tmp_dir),
                'Would run "git commit -m "Development mode for Horde-5.0.0"" now.'
            ),
            $this->output->getOutput()
        );
    }

    public function testPretendWithoutVersion()
    {
        $tmp_dir = $this->_prepareApplicationDirectory();
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($tmp_dir);
        $tasks->run(
            array('NextVersion', 'NextSentinel', 'CommitPostRelease'),
            $package,
            array(
                'next_note' => '',
                'pretend' => true,
                'commit' => new Components_Helper_Commit(
                    $this->output,
                    array('pretend' => true)
                )
            )
        );
        $this->assertEquals(
            array(
                sprintf('Would add next version "5.0.1" with the initial note "" to %s/package.xml now.', $tmp_dir),
                sprintf('Would extend sentinel in %s/docs/CHANGES with "5.0.1-git" now.', $tmp_dir),
                sprintf('Would replace sentinel in %s/lib/Application.php with "5.0.1-git" now.', $tmp_dir),
                sprintf('Would run "git add %s/package.xml" now.', $tmp_dir),
                sprintf('Would run "git add %s/docs/CHANGES" now.', $tmp_dir),
                sprintf('Would run "git add %s/lib/Application.php" now.', $tmp_dir),
                'Would run "git commit -m "Development mode for Horde-5.0.1"" now.'
            ),
            $this->output->getOutput()
        );
    }

    public function testPretendAlphaWithoutVersion()
    {
        $tmp_dir = $this->_prepareAlphaApplicationDirectory();
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($tmp_dir);
        $tasks->run(
            array('NextVersion', 'NextSentinel', 'CommitPostRelease'),
            $package,
            array(
                'next_note' => '',
                'pretend' => true,
                'commit' => new Components_Helper_Commit(
                    $this->output,
                    array('pretend' => true)
                )
            )
        );
        $this->assertEquals(
            array(
                sprintf('Would add next version "5.0.0alpha2" with the initial note "" to %s/package.xml now.', $tmp_dir),
                sprintf('Would extend sentinel in %s/docs/CHANGES with "5.0.0-git" now.', $tmp_dir),
                sprintf('Would replace sentinel in %s/lib/Application.php with "5.0.0-git" now.', $tmp_dir),
                sprintf('Would run "git add %s/package.xml" now.', $tmp_dir),
                sprintf('Would run "git add %s/docs/CHANGES" now.', $tmp_dir),
                sprintf('Would run "git add %s/lib/Application.php" now.', $tmp_dir),
                'Would run "git commit -m "Development mode for Horde-5.0.0"" now.'
            ),
            $this->output->getOutput()
        );
    }

    private function _prepareApplicationDirectory()
    {
        $tmp_dir = $this->getTemporaryDirectory();
        mkdir($tmp_dir . '/docs');
        file_put_contents($tmp_dir . '/docs/CHANGES', "---\nOLD\n---\nTEST");
        mkdir($tmp_dir . '/lib');
        file_put_contents($tmp_dir . '/lib/Application.php', "class Application {\npublic \$version = '5.0.0';\n}\n");
        file_put_contents(
            $tmp_dir . '/package.xml',
            '<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://pear.php.net/dtd/package-2.0">
 <name>Horde</name>
 <version>
  <release>5.0.0</release>
  <api>5.0.0</api>
 </version>
</package>'
        );
        return $tmp_dir;
    }

    private function _prepareAlphaApplicationDirectory()
    {
        $tmp_dir = $this->getTemporaryDirectory();
        mkdir($tmp_dir . '/docs');
        file_put_contents($tmp_dir . '/docs/CHANGES', "---\nOLD\n---\nTEST");
        mkdir($tmp_dir . '/lib');
        file_put_contents($tmp_dir . '/lib/Application.php', "class Application {\npublic \$version = '5.0.0-git';\n}\n");
        file_put_contents(
            $tmp_dir . '/package.xml',
            '<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://pear.php.net/dtd/package-2.0">
 <name>Horde</name>
 <version>
  <release>5.0.0alpha1</release>
  <api>5.0.0</api>
 </version>
</package>'
        );
        return $tmp_dir;
    }
}
