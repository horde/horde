<?php
/**
 * Test the version/stability check.
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
 * Test the version/stability check.
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
class Components_Unit_Components_Helper_VersionStabilityTest
extends Components_TestCase
{
    public function testStable()
    {
        $this->assertNull(
            Components_Helper_Version::validateReleaseStability(
                '4.0.0', 'stable'
            )
        );
    }

    public function testInvalidStable()
    {
        try {
            Components_Helper_Version::validateReleaseStability(
                '4.0.0', 'beta'
            );
            $this->fail('No exception!');
        } catch (Components_Exception $e) {
            $this->assertEquals(
                'Stable version "4.0.0" marked with invalid release stability "beta"!',
                $e->getMessage()
            );
        }
    }

    public function testAlpha()
    {
        $this->assertNull(
            Components_Helper_Version::validateReleaseStability(
                '4.0.0alpha1', 'alpha'
            )
        );
    }

    public function testInvalidAlpha()
    {
        try {
            Components_Helper_Version::validateReleaseStability(
                '4.0.0alpha1', 'stable'
            );
            $this->fail('No exception!');
        } catch (Components_Exception $e) {
            $this->assertEquals(
                'alpha version "4.0.0alpha1" marked with invalid release stability "stable"!',
                $e->getMessage()
            );
        }
    }

    public function testBeta()
    {
        $this->assertNull(
            Components_Helper_Version::validateReleaseStability(
                '4.0.0beta1', 'beta'
            )
        );
    }

    public function testInvalidBeta()
    {
        try {
            Components_Helper_Version::validateReleaseStability(
                '4.0.0beta1', 'stable'
            );
            $this->fail('No exception!');
        } catch (Components_Exception $e) {
            $this->assertEquals(
                'beta version "4.0.0beta1" marked with invalid release stability "stable"!',
                $e->getMessage()
            );
        }
    }

    public function testRc()
    {
        $this->assertNull(
            Components_Helper_Version::validateReleaseStability(
                '4.0.0RC1', 'beta'
            )
        );
    }

    public function testInvalidRc()
    {
        try {
            Components_Helper_Version::validateReleaseStability(
                '4.0.0RC1', 'stable'
            );
            $this->fail('No exception!');
        } catch (Components_Exception $e) {
            $this->assertEquals(
                'beta version "4.0.0RC1" marked with invalid release stability "stable"!',
                $e->getMessage()
            );
        }
    }

    public function testDev()
    {
        $this->assertNull(
            Components_Helper_Version::validateReleaseStability(
                '4.0.0dev1', 'devel'
            )
        );
    }

    public function testInvalidDev()
    {
        try {
            Components_Helper_Version::validateReleaseStability(
                '4.0.0dev1', 'stable'
            );
            $this->fail('No exception!');
        } catch (Components_Exception $e) {
            $this->assertEquals(
                'devel version "4.0.0dev1" marked with invalid release stability "stable"!',
                $e->getMessage()
            );
        }
    }

    public function testApiRc()
    {
        $this->assertNull(
            Components_Helper_Version::validateApiStability(
                '4.0.0RC1', 'beta'
            )
        );
    }

    public function testApiStable()
    {
        $this->assertNull(
            Components_Helper_Version::validateApiStability(
                '4.0.0', 'stable'
            )
        );
    }

    public function testInvalidApiStable()
    {
        try {
            Components_Helper_Version::validateApiStability(
                '4.0.0', 'beta'
            );
            $this->fail('No exception!');
        } catch (Components_Exception $e) {
            $this->assertEquals(
                'Stable version "4.0.0" marked with invalid api stability "beta"!',
                $e->getMessage()
            );
        }
    }


}