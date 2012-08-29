<?php
/**
 * Test the version helper.
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
 * Test the version helper.
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
class Components_Unit_Components_Helper_VersionTest
extends Components_TestCase
{
    public function testNextVersion()
    {
        $this->assertEquals(
            '5.0.1-git',
            Components_Helper_Version::nextVersion('5.0.0')
        );
        $this->assertEquals(
            '5.0.0-git',
            Components_Helper_Version::nextVersion('5.0.0RC1')
        );
        $this->assertEquals(
            '5.0.0-git',
            Components_Helper_Version::nextVersion('5.0.0alpha1')
        );
    }

    public function testNextPearVersion()
    {
        $this->assertEquals(
            '5.0.1',
            Components_Helper_Version::nextPearVersion('5.0.0')
        );
        $this->assertEquals(
            '5.0.0RC2',
            Components_Helper_Version::nextPearVersion('5.0.0RC1')
        );
        $this->assertEquals(
            '5.0.0alpha2',
            Components_Helper_Version::nextPearVersion('5.0.0alpha1')
        );
    }
}