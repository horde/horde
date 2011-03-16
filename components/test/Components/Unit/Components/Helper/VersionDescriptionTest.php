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
class Components_Unit_Components_Helper_VersionDescriptionTest
extends Components_TestCase
{
    public function testAlpha()
    {
        $this->assertEquals(
            '4.0 Alpha',
            Components_Helper_Version::pearToTicketDescription('4.0.0alpha1')
        );
    }

    public function testBeta()
    {
        $this->assertEquals(
            '4.0 Beta',
            Components_Helper_Version::pearToTicketDescription('4.0.0beta1')
        );
    }

    public function testRc1()
    {
        $this->assertEquals(
            '4.0 Release Candidate 1',
            Components_Helper_Version::pearToTicketDescription('4.0.0rc1')
        );
    }

    public function testRc2()
    {
        $this->assertEquals(
            '4.0 Release Candidate 2',
            Components_Helper_Version::pearToTicketDescription('4.0.0rc2')
        );
    }

    public function testFourOh()
    {
        $this->assertEquals(
            '4.0 Final',
            Components_Helper_Version::pearToTicketDescription('4.0.0')
        );
    }

    public function testFourOneOh()
    {
        $this->assertEquals(
            '4.1 Final',
            Components_Helper_Version::pearToTicketDescription('4.1.0')
        );
    }

    public function testFourOneOhBeta1()
    {
        $this->assertEquals(
            '4.1 Beta',
            Components_Helper_Version::pearToTicketDescription('4.1.0beta1')
        );
    }

    public function testFiveOh()
    {
        $this->assertEquals(
            '5.0 Final',
            Components_Helper_Version::pearToTicketDescription('5.0.0')
        );
    }

    public function testFiveTwoOhRc2()
    {
        $this->assertEquals(
            '5.2 Release Candidate 2',
            Components_Helper_Version::pearToTicketDescription('5.2.0rc2')
        );
    }

}