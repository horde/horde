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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the version helper.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Components_Unit_Components_Helper_VersionTest
extends Components_TestCase
{
    public function testAlpha()
    {
        $this->assertEquals(
            '4.0-ALPHA1',
            Components_Helper_Version::pearToHorde('4.0.0alpha1')
        );
    }

    public function testBeta()
    {
        $this->assertEquals(
            '4.0-BETA1',
            Components_Helper_Version::pearToHorde('4.0.0beta1')
        );
    }

    public function testRc1()
    {
        $this->assertEquals(
            '4.0-RC1',
            Components_Helper_Version::pearToHorde('4.0.0rc1')
        );
    }

    public function testRc2()
    {
        $this->assertEquals(
            '4.0-RC2',
            Components_Helper_Version::pearToHorde('4.0.0rc2')
        );
    }

    public function testFourOh()
    {
        $this->assertEquals(
            '4.0',
            Components_Helper_Version::pearToHorde('4.0.0')
        );
    }

    public function testFourOhOneGit()
    {
        $this->assertEquals(
            '4.0.1-git',
            Components_Helper_Version::pearToHorde('4.0.1-git')
        );
    }

    public function testFourOneOh()
    {
        $this->assertEquals(
            '4.1',
            Components_Helper_Version::pearToHorde('4.1.0')
        );
    }

    public function testFourOneOhBeta1()
    {
        $this->assertEquals(
            '4.1-BETA1',
            Components_Helper_Version::pearToHorde('4.1.0beta1')
        );
    }

    public function testFiveOh()
    {
        $this->assertEquals(
            '5.0',
            Components_Helper_Version::pearToHorde('5.0.0')
        );
    }

    public function testFiveTwoOhRc2()
    {
        $this->assertEquals(
            '5.2-RC2',
            Components_Helper_Version::pearToHorde('5.2.0rc2')
        );
    }

}