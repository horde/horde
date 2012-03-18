<?php
/**
 * Test the package release task.
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
 * Test the package release task.
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
class Components_Unit_Components_Release_Task_PackageTest
extends Components_TestCase
{
    public function testValidateSucceeds()
    {
        $package = $this->_getPackage();
        $task = $this->getReleaseTask('Package', $package);
        $this->assertEquals(
            array(),
            $task->validate(array('releaseserver' => 'A', 'releasedir' => 'B'))
        );
    }

    public function testNoReleaseServer()
    {
        $package = $this->_getPackage();
        $task = $this->getReleaseTask('Package', $package);
        $this->assertEquals(
            array('The "releaseserver" option has no value. Where should the release be uploaded?'),
            $task->validate(array('releasedir' => 'B'))
        );
    }

    public function testNoReleaseDir()
    {
        $package = $this->_getPackage();
        $task = $this->getReleaseTask('Package', $package);
        $this->assertEquals(
            array('The "releasedir" option has no value. Where is the remote pirum install located?'),
            $task->validate(array('releaseserver' => 'A'))
        );
    }

    public function testRunTaskWithoutUpload()
    {
        $package = $this->_getPackage();
        $package->expects($this->once())
            ->method('placeArchive');
        $this->getReleaseTasks()->run(
            array('Package'),
            $package,
            array('releaseserver' => 'A', 'releasedir' => 'B')
        );
    }

    public function testPretend()
    {
        $package = $this->_getPackage();
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('NAME'));
        $this->getReleaseTasks()->run(
            array('Package'),
            $package,
            array(
                'releaseserver' => 'A',
                'releasedir' => 'B',
                'pretend' => true,
                'upload' => true
            )
        );
        $this->assertEquals(
            array(
                'Would package NAME now.',
                'Would run "scp [PATH TO RESULTING]/[PACKAGE.TGZ - PRETEND MODE] A:~/" now.',
                'Would run "ssh A "pirum add B ~/[PACKAGE.TGZ - PRETEND MODE] && rm [PACKAGE.TGZ - PRETEND MODE]"" now.'
            ),
            $this->output->getOutput()
        );
    }

    private function _getPackage()
    {
        $package = $this->getMock('Components_Component', array(), array(), '', false, false);
        $package->expects($this->any())
            ->method('getState')
            ->will($this->returnValue('stable'));
        $package->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.0'));
        return $package;
    }
}