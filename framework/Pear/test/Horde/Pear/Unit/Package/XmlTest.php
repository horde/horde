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
        $xml = $this->_getFixture();
        $this->assertEquals('Fixture', $xml->getName());
    }

    public function testTimestamp()
    {
        $xml = $this->_getFixture();
        $xml->timestamp();
        $this->assertContains('<date>' . date('Y-m-d') . '</date>', (string) $xml);
    }

    public function testModifiedReleaseDate()
    {
        $xml = $this->_getFixture();
        $xml->timestamp();
        $this->assertEquals(date('Y-m-d'), $xml->findNode('/p:package/p:changelog/p:release/p:date')->textContent);
    }

    public function testAddNotePrimary()
    {
        $xml = $this->_getFixture();
        $xml->addNote('TEST');
        $this->assertEquals(
            '
* TEST
* Fixed bug #1
* Initial release
 ',
            $xml->findNode('/p:package/p:notes')->textContent
        );
    }

    public function testAddNoteChangelog()
    {
        $xml = $this->_getFixture();
        $xml->addNote('TEST');
        $this->assertEquals(
            '
* TEST
* Fixed bug #1
* Initial release
   ',
            $xml->findNode('/p:package/p:changelog/p:release/p:notes')->textContent
        );
    }

    public function testEquality()
    {
        $orig = file_get_contents(dirname(__FILE__) . '/../../fixture/simple/package.xml');
        $xml = $this->_getFixture();
        $this->assertEquals($orig, (string) $xml);
    }

    public function testSyncApi()
    {
        $this->_assertNodeContent(
            $this->_getSyncedFixture(),
            '/p:package/p:changelog/p:release/p:version/p:api',
            '0.0.2'
        );
    }

    public function testSyncReleaseStability()
    {
        $this->_assertNodeContent(
            $this->_getSyncedFixture(),
            '/p:package/p:changelog/p:release/p:stability/p:release',
            'beta'
        );
    }

    public function testSyncApiStability()
    {
        $this->_assertNodeContent(
            $this->_getSyncedFixture(),
            '/p:package/p:changelog/p:release/p:stability/p:api',
            'beta'
        );
    }

    public function testSyncDate()
    {
        $this->_assertNodeContent(
            $this->_getSyncedFixture(),
            '/p:package/p:changelog/p:release/p:date',
            '2010-08-22'
        );
    }

    public function testSyncLicense()
    {
        $this->_assertNodeContent(
            $this->_getSyncedFixture(),
            '/p:package/p:changelog/p:release/p:license',
            'LGPLv2.1'
        );
    }

    public function testSyncLicenseUrl()
    {
        $this->markTestIncomplete('I\'m unable to figure out how to access the "uri" attribute here.');
        $this->_assertAttributeContent(
            $this->_getSyncedFixture(),
            '/p:package/p:changelog/p:release/p:license',
            'url',
            'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html'
        );
    }

    public function testSyncNotes()
    {
        $this->_assertNodeContent(
            $this->_getSyncedFixture(),
            '/p:package/p:changelog/p:release/p:notes',
            '
* Fixed bug #1
* Initial release
   '
        );
    }

    private function _assertNodeContent($xml, $xpath, $content)
    {
        $this->assertEquals(
            $content, 
            $xml->findNode($xpath)->textContent
        );
    }

    private function _assertAttributeContent($xml, $xpath, $attribute, $content)
    {
        $this->assertEquals(
            $content, 
            $xml->findNode($xpath)
            ->getAttributeNS(Horde_Pear_Package_Xml::XMLNAMESPACE, 'uri')
        );
    }

    private function _getSyncedFixture()
    {
        $xml = $this->_getFixture();
        $xml->syncCurrentVersion();
        return $xml;
    }

    private function _getFixture()
    {
        return new Horde_Pear_Package_Xml(
            fopen(dirname(__FILE__) . '/../../fixture/simple/package.xml', 'r')
        );
    }
}
