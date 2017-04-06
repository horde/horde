<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */

/**
 * Test the HordeTheme package type.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */
class Horde_Pear_Unit_Package_Type_HordeThemeTest
extends Horde_Pear_TestCase
{
    public function testGetRoot()
    {
        $this->assertEquals(
            $this->_getFixtureBase(),
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
            'Horde_Pear_Package_Contents_Include_Patterns',
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

    public function testType()
    {
        $this->assertEquals(
            'Application',
            $this->_getFixture()->getType()
        );
    }

    public function testRole()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_Role_HordeApplication',
            $this->_getFixture()->getRole()
        );
    }

    public function testInstallAs()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_InstallAs_HordeTheme',
            $this->_getFixture()->getInstallAs()
        );
    }

    private function _getFixture()
    {
        return new Horde_Pear_Package_Type_HordeTheme(
            $this->_getFixturePath(), $this->_getFixtureBase()
        );
    }

    private function _getFixturePath()
    {
        return $this->_getFixtureBase() . '/horde/theme/mytheme';
    }

    private function _getFixtureBase()
    {
        return __DIR__ . '/../../../fixture/horde';
    }
}
