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

    public function testEmptyNote()
    {
        $xml = $this->_getFixture();
        $xml->addNote('');
        $this->assertEquals(
            '
* 
* Fixed bug #1
* Initial release
 ',
            $xml->findNode('/p:package/p:notes')->textContent
        );
    }

    public function testAddNoteToEmpty()
    {
        $xml = $this->_getEmptyNotesFixture();
        $xml->addNote('TEST');
        $this->assertEquals(
            '
* TEST
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

    public function testNewVersionVersion()
    {
        $xml = $this->_getFixture();
        $xml->addNextVersion('1.0.0', 'TEST');
        $this->assertEquals(
            '1.0.0',
            $xml->findNode('/p:package/p:version/p:release')->textContent
        );
    }

    public function testGetLicense()
    {
        $xml = $this->_getFixture();
        $this->assertEquals(
            array(
                'name' => 'LGPLv2.1',
                'uri' => 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html'
            ),
            $xml->getLicense()
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
        $xml = $this->_getSyncedFixture();
        $this->assertEquals(
            'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html', 
            $xml->findNode('/p:package/p:changelog/p:release')
            ->getElementsByTagNameNS(Horde_Pear_Package_Xml::XMLNAMESPACE, 'license')
            ->item(0)
            ->getAttribute('uri')
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

    public function testCreateContents()
    {
        $this->_assertNodeExists(
            $this->_getUpdatedContents(dirname(__FILE__) . '/../../fixture/empty'),
            '/p:package/p:contents'
        );
    }

    public function testCreateContentsDir()
    {
        $this->_assertNodeExists(
            $this->_getUpdatedContents(dirname(__FILE__) . '/../../fixture/empty'),
            '/p:package/p:contents/p:dir'
        );
    }

    public function testUpdateContentLine()
    {
        $this->_assertContentsContain(
            'File.php',
            $this->_getUpdatedContents(dirname(__FILE__) . '/../../fixture/empty')
        );
    }

    public function testUpdateWithDepth()
    {
        $this->_assertContentsContain(
            'lib/Stays.php',
            $this->_getUpdatedContents(dirname(__FILE__) . '/../../fixture/simple-empty')
        );
    }

    public function testUpdateTree()
    {
        $this->_assertContentsContain(
            'test/Horde/a.php',
            $this->_getUpdatedContents(dirname(__FILE__) . '/../../fixture/tree')
        );
    }

    public function testUpdateRemoval()
    {
        $this->_assertContentsNotContain(
            'lib/Old.php',
            $this->_getUpdatedContents(dirname(__FILE__) . '/../../fixture/remove')
        );
    }

    public function testRole()
    {
        $xml = $this->_getUpdatedContents(dirname(__FILE__) . '/../../fixture/simple-empty');
        $file = $this->_getContentsFile('lib/Stays.php', $xml);
        $this->assertEquals('php', $file->getAttribute('role'));
    }


    private function _assertNodeExists($xml, $xpath)
    {
        $this->assertInstanceOf(
            'DOMNode', 
            $xml->findNode($xpath)
        );
    }

    private function _assertContentsContain($filename, $xml)
    {
        $this->_assertDirectoryContains(
            $xml,
            $this->_getSubDirFromRoot($filename, $xml),
            basename($filename)
        );
    }

    private function _assertContentsNotContain($filename, $xml)
    {
        $this->_assertDirectoryNotContains(
            $xml, 
            $this->_getSubDirFromRoot($filename, $xml),
            basename($filename)
        );
    }

    private function _getContentsFile($filename, $xml)
    {
        $subdir = $this->_getSubDirFromRoot($filename, $xml);
        foreach ($xml->findNodesRelativeTo('./p:file', $subdir) as $file) {
            $name = $file->getAttribute('name');
            if ($name == basename($filename)) {
                return $file;
            }
        }
    }

    private function _getSubDirFromRoot($filename, $xml)
    {
        $this->_assertNodeExists($xml, '/p:package/p:contents');
        $this->_assertNodeExists($xml, '/p:package/p:contents/p:dir');
        $dir = $xml->findNode('/p:package/p:contents/p:dir');
        return $this->_getSubDir($xml, $dir, $filename);
    }

    private function _getSubDir($xml, $dir, $filename)
    {
        if (strpos($filename, DIRECTORY_SEPARATOR) === false) {
            return $dir;
        }
        $parts = explode(DIRECTORY_SEPARATOR, $filename);
        $start = array_shift($parts);
        $rest = join(DIRECTORY_SEPARATOR, $parts);
        $contents = array();
        foreach ($xml->findNodesRelativeTo('./p:dir', $dir) as $subdir) {
            $name = $subdir->getAttribute('name');
            if ($name == $start) {
                return $this->_getSubDir($xml, $subdir, $rest);
            }
            $contents[] = $name;
        }
        $this->fail(
            sprintf(
                "Directory \"%s\" is not present among [\n%s\n]",
                $start,
                join(",\n", $contents)
            )
        );
    }

    private function _assertDirectoryContains($xml, $dir, $filename)
    {
        $contents = array();
        foreach ($xml->findNodesRelativeTo('./p:file', $dir) as $file) {
            $name = $file->getAttribute('name');
            if ($name == $filename) {
                $this->assertEquals($name, $filename);
                return;
            }
            $contents[] = $name;
        }
        $this->fail(
            sprintf(
                "File \"%s\" is not present among [\n%s\n]",
                $filename,
                join(",\n", $contents)
            )
        );
    }

    private function _assertDirectoryNotContains($xml, $dir, $filename)
    {
        $contents = array();
        foreach ($xml->findNodesRelativeTo('./p:file', $dir) as $file) {
            $contents[] = $file->getAttribute('name');
        }
        $this->assertNotContains(
            $filename,
            $contents
        );
    }

    private function _assertNodeContent($xml, $xpath, $content)
    {
        $this->assertEquals(
            $content, 
            $xml->findNode($xpath)->textContent
        );
    }

    private function _getSyncedFixture()
    {
        $xml = $this->_getFixture();
        $xml->syncCurrentVersion();
        return $xml;
    }

    private function _getUpdatedContents($package)
    {
        $xml = new Horde_Pear_Package_Xml(
            fopen($package . '/package.xml', 'r')
        );
        $factory = new Horde_Pear_Factory();
        $contents = $factory->createContents($package);
        $xml->updateContents($contents);
        return $xml;
    }

    private function _getFixture()
    {
        return new Horde_Pear_Package_Xml(
            fopen(dirname(__FILE__) . '/../../fixture/simple/package.xml', 'r')
        );
    }

    private function _getEmptyNotesFixture()
    {
        return new Horde_Pear_Package_Xml(
            fopen(dirname(__FILE__) . '/../../fixture/notes/package.xml', 'r')
        );
    }
}
