<?php
/**
 * Test the install paths for base applications.
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
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the install paths for base applications.
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
class Horde_Pear_Unit_Package_Contents_InstallAs_HordeTest
extends Horde_Pear_TestCase
{
    public function testInstallAsType()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_InstallAs_Horde',
            $this->_getFixture()->getInstallAs()
        );
    }

    public function testInstallAsForScripts()
    {
        $this->assertEquals(
            'horde-bin',
            $this->_getFixture()->getInstallAs()->getInstallAs('/bin/horde-bin', 'horde')
        );
    }

    public function testInstallAsForDocs()
    {
        $this->assertEquals(
            'doc.txt',
            $this->_getFixture()->getInstallAs()->getInstallAs('/docs/doc.txt', 'horde')
        );
    }

    public function testInstallAsForTests()
    {
        $this->assertEquals(
            'test.php',
            $this->_getFixture()->getInstallAs()->getInstallAs('/test/test.php', 'horde')
        );
    }

    public function testInstallAsForPhp()
    {
        $this->assertEquals(
            'index.php',
            $this->_getFixture()->getInstallAs()->getInstallAs('/index.php', 'horde')
        );
    }

    private function _getFixture()
    {
        return new Horde_Pear_Package_Type_Horde(
            dirname(__FILE__) . '/../../../../fixture/horde/horde'
        );
    }
}
