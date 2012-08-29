<?php
/**
 * Test the dependency handling.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the dependency handling.
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
class Horde_Pear_Unit_Package_DependenciesTest
extends Horde_Pear_TestCase
{
    public function testPhp()
    {
        $result = array();
        Horde_Pear_Package_Dependencies::addDependency(
            array('min' => '5.2.0'), 'php', 'yes', $result
        );
        $this->assertEquals(
            array(
                array(
                    'type' => 'php',
                    'optional' => 'no',
                    'rel' => 'ge',
                    'version' => '5.2.0'
                )
            ),
            $result
        );
    }

    public function testPearinstaller()
    {
        $result = array();
        Horde_Pear_Package_Dependencies::addDependency(
            array('min' => '1.0.0', 'max' => '2.0.0'),
            'pearinstaller',
            'yes',
            $result
        );
        $this->assertEquals(
            array(
                array(
                    'type' => 'pkg',
                    'name' => 'PEAR',
                    'channel' => 'pear.php.net',
                    'optional' => 'no',
                    'rel' => 'ge',
                    'version' => '1.0.0'
                ),
                array(
                    'type' => 'pkg',
                    'name' => 'PEAR',
                    'channel' => 'pear.php.net',
                    'optional' => 'no',
                    'rel' => 'le',
                    'version' => '2.0.0'
                )
            ),
            $result
        );
    }

    public function testPackage()
    {
        $result = array();
        Horde_Pear_Package_Dependencies::addDependency(
            array('name' => 'test', 'channel' => 'x', 'min' => '1.0.0'),
            'package',
            'yes',
            $result
        );
        $this->assertEquals(
            array(
                array(
                    'name' => 'test',
                    'channel' => 'x',
                    'min' => '1.0.0',
                    'type' => 'pkg',
                    'optional' => 'yes',
                    'rel' => 'ge',
                    'version' => '1.0.0'
                ),
            ),
            $result
        );
    }

    public function testExtension()
    {
        $result = array();
        Horde_Pear_Package_Dependencies::addDependency(
            array('name' => 'Z'), 'extension', 'yes', $result
        );
        $this->assertEquals(
            array(
                array(
                    'name' => 'Z',
                    'type' => 'ext',
                    'optional' => 'yes',
                ),
            ),
            $result
        );
    }

    /**
     * @expectedException Horde_Pear_Exception
     */
    public function testUnsupported()
    {
        $result = array();
        Horde_Pear_Package_Dependencies::addDependency(
            array('name' => 'Z'), 'unsupported', 'yes', $result
        );
    }
}