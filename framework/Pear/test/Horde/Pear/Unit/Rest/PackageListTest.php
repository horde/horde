<?php
/**
 * Test the package list parser.
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
 * Test the package list parser.
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
class Horde_Pear_Unit_Rest_PackageListTest
extends Horde_Pear_TestCase
{
    public function testCount()
    {
        $pl = $this->_getPackageList();
        $this->assertEquals(2, count($pl));
    }

    public function testPackageName()
    {
        $pl = $this->_getPackageList();
        $this->assertEquals('Horde_ActiveSync', (string)$pl->p[0]);
    }

    public function testPackageLink()
    {
        $pl = $this->_getPackageList();
        $this->assertEquals('/rest/p/horde_activesync', $pl->p[0]['xlink:href']);
    }

    public function testGetPackages()
    {
        $this->assertEquals(
            array(
                'Horde_ActiveSync' => '/rest/p/horde_activesync',
                'Horde_Alarm' => '/rest/p/horde_alarm'
            ),
            $this->_getPackageList()->getPackages()
        );
    }

    public function testPackageNames()
    {
        $this->assertEquals(
            array('Horde_ActiveSync', 'Horde_Alarm'), 
            $this->_getPackageList()->listPackages()
        );
    }

    public function testGetPackageLink()
    {
        $this->assertEquals(
            '/rest/p/horde_alarm', 
            $this->_getPackageList()->getPackageLink('Horde_Alarm')
        );
    }

    /**
     * @expectedException Horde_Pear_Exception
     */
    public function testGetInvalidPackageLink()
    {
        $this->_getPackageList()->getPackageLink('Horde_NoSuchPackage');
    }

    private function _getPackageList()
    {
        return new Horde_Pear_Rest_PackageList(
            $this->_getList()
        );
    }

    private function _getList()
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
<l xmlns="http://pear.php.net/dtd/rest.categorypackages" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.categorypackages http://pear.php.net/dtd/rest.categorypackages.xsd">
  <p xlink:href="/rest/p/horde_activesync">Horde_ActiveSync</p>
  <p xlink:href="/rest/p/horde_alarm">Horde_Alarm</p>
</l>';
    }
}
