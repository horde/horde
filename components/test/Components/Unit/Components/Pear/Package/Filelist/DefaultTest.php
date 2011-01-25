<?php
/**
 * Test the handling of file lists.
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
require_once dirname(__FILE__) . '/../../../../../Autoload.php';

/**
 * Test the handling of file lists.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Components_Unit_Components_Pear_Package_Filelist_DefaultTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_old_errorreporting = error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));
        error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));
        if (!defined('PEAR_VALIDATE_INSTALLING')) {
            define('PEAR_VALIDATE_INSTALLING', 1);
            define('PEAR_VALIDATE_NORMAL', 3);
        }
    }

    public function tearDown()
    {
        error_reporting($this->_old_errorreporting);
    }

    /**
     * @dataProvider provideFiles
     */
    public function testInstall($role, $name, $as)
    {
        $package = $this->_getPackage(array('role' => $role, 'name' => $name));
        $package->expects($this->once())
            ->method('addInstallAs')
            ->with($name, $as);
        $this->_getFilelist($package)->update();
    }

    public function testEmpty()
    {
        $list = array('dir' => array('file' => array(array())));
        $package = $this->getMock('PEAR_PackageFile_v2_rw', array(), array(), '', false, false);
        $package->expects($this->once())
            ->method('getContents')
            ->will($this->returnValue($list));
        $package->expects($this->never())
            ->method('addUsesRole');
        $this->_getFilelist($package)->update();
    }

    public function testNewHordeRole()
    {
        $package = $this->_getPackage(array('role' => 'horde', 'name' => 'a'));
        $package->expects($this->once())
            ->method('addUsesRole')
            ->with('horde', 'Role', 'pear.horde.org');
        $this->_getFilelist($package)->update();
    }

    public function testPreviousHordeRole()
    {
        $package = $this->_getPackage(array('role' => 'horde', 'name' => 'a'));
        $package->expects($this->once())
            ->method('getUsesRole')
            ->will($this->returnValue(array('role' => 'horde')));
        $package->expects($this->never())
            ->method('addUsesRole');
        $this->_getFilelist($package)->update();
    }

    public function testApplication()
    {
        $package = $this->_getPackage(array('role' => 'horde', 'name' => 'a'));
        $package->expects($this->once())
            ->method('getUsesRole')
            ->will($this->returnValue(array('role' => 'horde')));
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('imp'));
        $package->expects($this->once())
            ->method('addInstallAs')
            ->with('a', 'imp/a');
        $this->_getFilelist($package)->update();
    }

    private function _getPackage(array $contents)
    {
        $list = array('dir' => array('file' => array(array('attribs' => $contents))));
        $package = $this->getMock('PEAR_PackageFile_v2_rw', array(), array(), '', false, false);
        $package->expects($this->once())
            ->method('getContents')
            ->will($this->returnValue($list));
        return $package;
    }

    private function _getFilelist(PEAR_PackageFile_v2_rw $package)
    {
        return new Components_Pear_Package_Filelist_Default($package);
    }

    public function provideFiles()
    {
        return array(
            array('horde', 'a', 'a'),
            array('doc', 'doc/a', 'a'),
            array('doc', 'README', 'README'),
            array('test', 'test/AllTest.php', 'AllTest.php'),
            array('script', 'script/something.php', 'something'),
            array('script', 'bin/runthis', 'runthis'),
            array('php', 'lib/Library.php', 'Library.php'),
            array('php', 'view.php', 'view.php'),
            array('data', 'data/table.sql', 'table.sql'),
            array('data', 'locale/de.mo', 'locale/de.mo'),
            array('data', 'migration/Horde/Alarm/1_horde_alarms_table.php', 'Horde/Alarm/migration/1_horde_alarms_table.php'),
            array('data', 'somedata', 'somedata'),
        );
    }
}