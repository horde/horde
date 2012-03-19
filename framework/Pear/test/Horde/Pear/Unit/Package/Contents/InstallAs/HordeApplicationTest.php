<?php
/**
 * Test the install paths for horde applications.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the install paths for horde applications.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_Package_Contents_InstallAs_HordeApplicationTest
extends Horde_Pear_TestCase
{
    public function testInstallAsType()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_InstallAs_HordeApplication',
            $this->_getFixture()->getInstallAs()
        );
    }

    public function testInstallAsForScripts()
    {
        $this->assertEquals(
            'horde-bin',
            $this->_getFixture()->getInstallAs()->getInstallAs('/bin/horde-bin', 'imp')
        );
    }

    public function testInstallAsForDocs()
    {
        $this->assertEquals(
            'doc.txt',
            $this->_getFixture()->getInstallAs()->getInstallAs('/docs/doc.txt', 'imp')
        );
    }

    public function testInstallAsForTests()
    {
        $this->assertEquals(
            'test.php',
            $this->_getFixture()->getInstallAs()->getInstallAs('/test/test.php', 'imp')
        );
    }

    public function testInstallAsForPhp()
    {
        $this->assertEquals(
            'imp/index.php',
            $this->_getFixture()->getInstallAs()->getInstallAs('/index.php', 'imp')
        );
    }

    public function testInstallAsForReadme()
    {
        $this->assertEquals(
            'README',
            $this->_getFixture()->getInstallAs()->getInstallAs('/README', 'imp')
        );
    }

    private function _getFixture()
    {
        return new Horde_Pear_Package_Type_Horde(
            __DIR__ . '/../../../../fixture/horde/imp'
        );
    }
}
