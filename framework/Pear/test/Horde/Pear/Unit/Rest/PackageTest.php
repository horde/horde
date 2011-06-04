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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the package information parser.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
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
        $this->assertEquals('LGPL', $this->_getPackage()->getLicense());
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

    private function _getPackage()
    {
        return new Horde_Pear_Rest_Package(
            $this->_getInformation()
        );
    }
    private function _getInformation()
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
<p xmlns="http://pear.php.net/dtd/rest.package" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.package    http://pear.php.net/dtd/rest.package.xsd">
<n>Horde_Core</n>
<c>pear.horde.org</c>
<ca xlink:href="/rest/c/Default">Default</ca>
<l>LGPL</l>
<s>Horde Core Framework libraries</s>
<d>These classes provide the core functionality of the Horde Application Framework.</d>
<r xlink:href="/rest/r/horde_core" />
</p>';
    }
}
