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
 * Test the install paths for horde themes.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */
class Horde_Pear_Unit_Package_Contents_InstallAs_HordeThemeTest
extends Horde_Pear_TestCase
{
    public function testInstallAsType()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Contents_InstallAs_HordeTheme',
            $this->_getFixture()->getInstallAs()
        );
    }

    public function testInstallAsForInfo()
    {
        $this->assertEquals(
            'themes/mytheme/info.php',
            $this->_getFixture()->getInstallAs()->getInstallAs('/horde/themes/mytheme/info.php', 'horde')
        );
    }

    public function testInstallAsForCss()
    {
        $this->assertEquals(
            'themes/mytheme/screen.css',
            $this->_getFixture()->getInstallAs()->getInstallAs('/horde/themes/mytheme/screen.css', 'horde')
        );
        $this->assertEquals(
            'themes/mytheme/dynamic/screen.css',
            $this->_getFixture()->getInstallAs()->getInstallAs('/horde/themes/mytheme/dynamic/screen.css', 'horde')
        );
    }

    public function testInstallAsForGraphics()
    {
        $this->assertEquals(
            'themes/mytheme/graphics/logo.png',
            $this->_getFixture()->getInstallAs()->getInstallAs('/horde/themes/mytheme/graphics/logo.png', 'horde')
        );
    }

    public function testInstallAsForCssApplication()
    {
        $this->assertEquals(
            'imp/themes/mytheme/screen.css',
            $this->_getFixture()->getInstallAs()->getInstallAs('/imp/themes/mytheme/screen.css', 'imp')
        );
        $this->assertEquals(
            'imp/themes/mytheme/dynamic/screen.css',
            $this->_getFixture()->getInstallAs()->getInstallAs('/imp/themes/mytheme/dynamic/screen.css', 'imp')
        );
    }

    public function testInstallAsForGraphicsApplication()
    {
        $this->assertEquals(
            'imp/themes/mytheme/graphics/logo.png',
            $this->_getFixture()->getInstallAs()->getInstallAs('/imp/themes/mytheme/graphics/logo.png', 'imp')
        );
    }

    private function _getFixture()
    {
        return new Horde_Pear_Package_Type_HordeTheme(
            __DIR__ . '/../../../../fixture/horde/horde/themes/mytheme'
        );
    }
}
