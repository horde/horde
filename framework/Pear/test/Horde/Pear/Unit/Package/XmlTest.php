<?php
/**
 * Test the core package XML handler.
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
 * Test the core package XML handler.
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
class Horde_Pear_Unit_Package_XmlTest
extends Horde_Pear_TestCase
{
    public function testGetName()
    {
        $xml = new Horde_Pear_Package_Xml(
            fopen(dirname(__FILE__) . '/../../fixture/simple/package.xml', 'r')
        );
        $this->assertEquals('Fixture', $xml->getName());
    }

    public function testReleaseNow()
    {
        $xml = new Horde_Pear_Package_Xml(
            fopen(dirname(__FILE__) . '/../../fixture/simple/package.xml', 'r')
        );
        $xml->releaseNow();
        $this->assertContains('<date>' . date('Y-m-d') . '</date>', (string) $xml);
    }

    public function testModifiedReleaseDate()
    {
        $xml = new Horde_Pear_Package_Xml(
            fopen(dirname(__FILE__) . '/../../fixture/simple/package.xml', 'r')
        );
        $xml->releaseNow();
        $this->assertEquals(date('Y-m-d'), $xml->findNode('/p:package/p:changelog/p:release/p:date')->textContent);
    }

    public function testEquality()
    {
        $orig = file_get_contents(dirname(__FILE__) . '/../../fixture/simple/package.xml');
        $xml = new Horde_Pear_Package_Xml(
            fopen(dirname(__FILE__) . '/../../fixture/simple/package.xml', 'r')
        );
        $this->assertEquals($orig, (string) $xml);
    }
}
