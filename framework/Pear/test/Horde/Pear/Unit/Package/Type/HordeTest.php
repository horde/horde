<?php
/**
 * Test the Horde package type.
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
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the Horde package type.
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
class Horde_Pear_Unit_Package_Type_HordeTest
extends Horde_Pear_TestCase
{
    public function testGetRoot()
    {
        $this->assertEquals(
            $this->_getFixturePath(),
            $this->_getFixture()->getRootPath()
        );
    }

    public function testGetPackageXml()
    {
        $this->assertEquals(
            $this->_getFixturePath() . '/package.xml',
            $this->_getFixture()->getPackageXmlPath()
        );
    }

    public function testInclude()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_Include_All',
            $this->_getFixture()->getInclude()
        );
    }

    public function testIgnore()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_Ignore_Composite',
            $this->_getFixture()->getIgnore()
        );
    }

    public function testRepositoryRoot()
    {
        $this->assertEquals(
            $this->_getFixtureBase(),
            $this->_getFixture()->getRepositoryRoot()
        );
    }

    public function testGitIgnore()
    {
        $this->assertContains(
            '/lib/',
            $this->_getFixture()->getGitIgnore()
        );
    }

    public function testComponent()
    {
        $this->assertEquals(
            'Component',
            $this->_getFixture()->getType()
        );
    }

    public function testApplication()
    {
        $this->assertEquals(
            'Application',
            $this->_getApplicationFixture()->getType()
        );
    }

    public function testRoleComponent()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_Role_HordeComponent',
            $this->_getFixture()->getRole()
        );
    }

    public function testRoleApplication()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_Role_HordeApplication',
            $this->_getApplicationFixture()->getRole()
        );
    }

    public function testInstallAsComponent()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_InstallAs_HordeComponent',
            $this->_getFixture()->getInstallAs()
        );
    }

    public function testInstallAsApplication()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_InstallAs_Horde',
            $this->_getApplicationFixture()->getInstallAs()
        );
    }

    private function _getFixture()
    {
        return new Horde_Pear_Package_Type_Horde($this->_getFixturePath());
    }

    private function _getApplicationFixture()
    {
        return new Horde_Pear_Package_Type_Horde(
            $this->_getFixtureBase() . '/horde'
        );
    }

    private function _getFixturePath()
    {
        return $this->_getFixtureBase() . '/framework/simple';
    }

    private function _getFixtureBase()
    {
        return __DIR__ . '/../../../fixture/horde';
    }
}
