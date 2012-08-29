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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the current sentinel release task.
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
class Components_Unit_Components_Release_Task_CurrentSentinelTest
extends Components_TestCase
{
    public function testRunTaskWithoutCommit()
    {
        $tmp_dir = $this->_prepareApplicationDirectory();
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($tmp_dir);
        $tasks->run(array('CurrentSentinel'), $package);
        $this->assertEquals(
            '---------
v4.0.1RC1
---------
TEST',
            file_get_contents($tmp_dir . '/docs/CHANGES')
        );
        $this->assertEquals(
            'class Application {
public $version = \'4.0.1RC1\';
}
',
            file_get_contents($tmp_dir . '/lib/Application.php')
        );
    }

    public function testRunTaskWithoutCommitOnBundle()
    {
        $tmp_dir = $this->_prepareApplicationDirectory(true);
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($tmp_dir);
        $tasks->run(array('CurrentSentinel'), $package);
        $this->assertEquals(
            '---------
v4.0.1RC1
---------
TEST',
            file_get_contents($tmp_dir . '/docs/CHANGES')
        );
        $this->assertEquals(
            'class Horde_Bundle {
const VERSION = \'4.0.1RC1\';
}
',
            file_get_contents($tmp_dir . '/lib/Bundle.php')
        );
    }

    public function testPretend()
    {
        $tmp_dir = $this->_prepareApplicationDirectory();
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($tmp_dir);
        $tasks->run(
            array('CurrentSentinel', 'CommitPreRelease'),
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
                sprintf('Would replace sentinel in %s/docs/CHANGES with "4.0.1RC1" now.', $tmp_dir),
                sprintf('Would replace sentinel in %s/lib/Application.php with "4.0.1RC1" now.', $tmp_dir),
                sprintf('Would run "git add %s/docs/CHANGES" now.', $tmp_dir),
                sprintf('Would run "git add %s/lib/Application.php" now.', $tmp_dir),
                'Would run "git commit -m "Released Horde-4.0.1RC1"" now.'
            ),
            $this->output->getOutput()
        );
    }

    public function testPretendOnBundle()
    {
        $tmp_dir = $this->_prepareApplicationDirectory(true);
        $tasks = $this->getReleaseTasks();
        $package = $this->getComponent($tmp_dir);
        $tasks->run(
            array('CurrentSentinel', 'CommitPreRelease'),
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
                sprintf('Would replace sentinel in %s/docs/CHANGES with "4.0.1RC1" now.', $tmp_dir),
                sprintf('Would replace sentinel in %s/lib/Bundle.php with "4.0.1RC1" now.', $tmp_dir),
                sprintf('Would run "git add %s/docs/CHANGES" now.', $tmp_dir),
                sprintf('Would run "git add %s/lib/Bundle.php" now.', $tmp_dir),
                'Would run "git commit -m "Released Horde-4.0.1RC1"" now.'
            ),
            $this->output->getOutput()
        );
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
        file_put_contents(
            $tmp_dir . '/package.xml',
            '<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://pear.php.net/dtd/package-2.0">
 <name>Horde</name>
 <version>
  <release>4.0.1RC1</release>
  <api>4.0.0</api>
 </version>
</package>'
        );
        return $tmp_dir;
    }
}