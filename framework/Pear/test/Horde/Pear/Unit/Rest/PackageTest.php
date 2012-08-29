<?php
/**
 * Test the package information parser.
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
 * Test the package information parser.
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
class Horde_Pear_Unit_Rest_PackageTest
extends Horde_Pear_TestCase
{
    public function testName()
    {
        $this->assertEquals('Horde_Core', $this->_getPackage()->getName());
    }

    public function testChannel()
    {
        $this->assertEquals('pear.horde.org', $this->_getPackage()->getChannel());
    }

    public function testLicense()
    {
        $this->assertEquals('LGPL-2.1', $this->_getPackage()->getLicense());
    }

    public function testSummary()
    {
        $this->assertEquals(
            'Horde Core Framework libraries',
            $this->_getPackage()->getSummary()
        );
    }

    public function testDescription()
    {
        $this->assertEquals(
            'These classes provide the core functionality of the Horde Application Framework.',
            $this->_getPackage()->getDescription()
        );
    }

    public function testDescriptionFromStream()
    {
        $this->assertEquals(
            'These classes provide the core functionality of the Horde Application Framework.',
            $this->_getStreamPackage()->getDescription()
        );
    }

    private function _getPackage()
    {
        return new Horde_Pear_Rest_Package(
            $this->_getInformation()
        );
    }

    private function _getStreamPackage()
    {
        return new Horde_Pear_Rest_Package(
            fopen(__DIR__ . '/../../fixture/rest/package.xml', 'r')
        );
    }

    private function _getInformation()
    {
        return file_get_contents(
            __DIR__ . '/../../fixture/rest/package.xml'
        );
    }
}
