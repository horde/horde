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
class Components_Unit_Components_Helper_VersionDescriptionTest
extends Components_TestCase
{
    public function testAlpha()
    {
        $this->assertEquals(
            '4.0.0 Alpha 1',
            Components_Helper_Version::pearToTicketDescription('4.0.0alpha1')
        );
    }

    public function testBeta()
    {
        $this->assertEquals(
            '4.0.0 Beta 1',
            Components_Helper_Version::pearToTicketDescription('4.0.0beta1')
        );
    }

    public function testRc1()
    {
        $this->assertEquals(
            '4.0.0 Release Candidate 1',
            Components_Helper_Version::pearToTicketDescription('4.0.0RC1')
        );
    }

    public function testRc2()
    {
        $this->assertEquals(
            '4.0.0 Release Candidate 2',
            Components_Helper_Version::pearToTicketDescription('4.0.0RC2')
        );
    }

    public function testFourOh()
    {
        $this->assertEquals(
            '4.0.0 Final',
            Components_Helper_Version::pearToTicketDescription('4.0.0')
        );
    }

    public function testFourOneOh()
    {
        $this->assertEquals(
            '4.1.0 Final',
            Components_Helper_Version::pearToTicketDescription('4.1.0')
        );
    }

    public function testFourOneOhBeta1()
    {
        $this->assertEquals(
            '4.1.0 Beta 1',
            Components_Helper_Version::pearToTicketDescription('4.1.0beta1')
        );
    }

    public function testFiveOh()
    {
        $this->assertEquals(
            '5.0.0 Final',
            Components_Helper_Version::pearToTicketDescription('5.0.0')
        );
    }

    public function testFiveTwoOhRc2()
    {
        $this->assertEquals(
            '5.2.0 Release Candidate 2',
            Components_Helper_Version::pearToTicketDescription('5.2.0RC2')
        );
    }

}