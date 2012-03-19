<?php
/**
 * Test the Snapshot module.
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
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the Snapshot module.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Components_Unit_Components_Module_SnapshotTest
extends Components_TestCase
{
    public function testSnapshotOption()
    {
        $this->assertRegExp('/-z,\s*--snapshot/', $this->getHelp());
    }

    public function testSnapshotAction()
    {
        $this->assertRegExp('/ACTION "snapshot"/', $this->getActionHelp('snapshot'));
    }

    public function testSnapshot()
    {
        $tmp_dir = Horde_Util::createTempDir();
        $_SERVER['argv'] = array(
            'horde-components',
            '--verbose',
            '--snapshot',
            '--destination=' . $tmp_dir,
            __DIR__ . '/../../../fixture/framework/Install'
        );
        $this->_callUnstrictComponents();
        $this->fileRegexpPresent(
            '/Install-[0-9]+(\.[0-9]+)+([a-z0-9]+)?/', $tmp_dir
        );
    }

    public function testKeepVersion()
    {
        $tmp_dir = Horde_Util::createTempDir();
        $_SERVER['argv'] = array(
            'horde-components',
            '--keep-version',
            '--snapshot',
            '--destination=' . $tmp_dir,
            __DIR__ . '/../../../fixture/framework/Install'
        );
        $this->_callUnstrictComponents();
        $this->fileRegexpPresent('/Install-0.0.1/', $tmp_dir);
    }

    public function testError()
    {
        $this->setPearGlobals();
        $cwd = getcwd();
        $tmp_dir = Horde_Util::createTempDir();
        $_SERVER['argv'] = array(
            'horde-components',
            '--verbose',
            '--snapshot',
            '--destination=' . $tmp_dir,
            __DIR__ . '/../../../fixture/simple'
        );
        try {
            $this->_callUnstrictComponents();
        } catch (Components_Exception_Pear $e) {
            ob_end_clean();
            $this->assertContains(
                'PEAR_Packagefile_v2::toTgz: invalid package.xml',
                (string) $e
            );
            $this->assertContains(
                'Old.php" in package.xml does not exist',
                $e
            );
        }
        chdir($cwd);
    }
}