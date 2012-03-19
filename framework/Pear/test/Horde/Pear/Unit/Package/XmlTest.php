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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the core package XML handler.
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
class Horde_Pear_Unit_Package_XmlTest
extends Horde_Pear_TestCase
{
    public function testGetName()
    {
        $xml = $this->_getFixture();
        $this->assertEquals('Fixture', $xml->getName());
    }

    public function testGetChannel()
    {
        $xml = $this->_getFixture();
        $this->assertEquals('pear.php.net', $xml->getChannel());
    }

    public function testGetVersion()
    {
        $xml = $this->_getFixture();
        $this->assertEquals('0.0.1', $xml->getVersion());
    }

    public function testGetSummary()
    {
        $xml = $this->_getFixture();
        $this->assertEquals('Test fixture.', $xml->getSummary());
    }

    public function testGetDescription()
    {
        $xml = $this->_getFixture();
        $this->assertEquals(
            'A dummy package.xml used for testing the Components package.',
            $xml->getDescription()
        );
    }

    public function testReleaseState()
    {
        $xml = $this->_getFixture();
        $this->assertEquals('beta', $xml->getState('release'));
    }

    public function testApiState()
    {
        $xml = $this->_getFixture();
        $this->assertEquals('beta', $xml->getState('api'));
    }

    public function testGetLeads()
    {
        $xml = $this->_getFixture();
        $this->assertEquals(
            array(
                array(
                    'name' => 'Gunnar Wrobel',
                    'user' => 'wrobel',
                    'email' => 'p@rdus.de',
                    'active' => 'yes',
                )
            ),
            $xml->getLeads()
        );
    }

    public function testGetDependencies()
    {
        $xml = $this->_getFixture();
        $this->assertEquals(
            array(
                array(
                    'type' => 'php',
                    'optional' => 'no',
                    'rel' => 'ge',
                    'version' => '5.0.0',
                ),
                array(
                    'type' => 'pkg',
                    'name' => 'PEAR',
                    'channel' => 'pear.php.net',
                    'optional' => 'no',
                    'rel' => 'ge',
                    'version' => '1.7.0',
                )
            ),
            $xml->getDependencies()
        );
    }

    public function testGetNotes()
    {
        $this->assertEquals('
* Fixed bug #1
* Initial release
 ', $this->_getFixture()->getNotes());
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

    public function testSetReleaseVersion()
    {
        $xml = $this->_getFixture();
        $xml->setVersion('6.0.0');
        $this->assertEquals(
            '6.0.0',
            $xml->findNode('/p:package/p:version/p:release')->textContent
        );
    }

    public function testSetApiVersion()
    {
        $xml = $this->_getFixture();
        $xml->setVersion(null, '6.0.0');
        $this->assertEquals(
            '6.0.0',
            $xml->findNode('/p:package/p:version/p:api')->textContent
        );
    }

    public function testGetLicense()
    {
        $xml = $this->_getFixture();
        $this->assertEquals('LGPLv2.1', $xml->getLicense());
    }

    public function testGetLicenseLocation()
    {
        $xml = $this->_getFixture();
        $this->assertEquals(
            'http://www.horde.org/licenses/lgpl21',
            $xml->getLicenseLocation()
        );
    }

    public function testEquality()
    {
        $orig = file_get_contents(__DIR__ . '/../../fixture/horde/framework/simple/package.xml');
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
            'http://www.horde.org/licenses/lgpl21', 
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
            $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/empty'),
            '/p:package/p:contents'
        );
    }

    public function testCreateContentsDir()
    {
        $this->_assertNodeExists(
            $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/empty'),
            '/p:package/p:contents/p:dir'
        );
    }

    public function testUpdateContentLine()
    {
        $this->_assertContentsContain(
            'File.php',
            $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/empty')
        );
    }

    public function testUpdateWithDepth()
    {
        $this->_assertContentsContain(
            'lib/Stays.php',
            $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/simple-empty')
        );
    }

    public function testUpdateTree()
    {
        $this->_assertContentsContain(
            'test/Horde/a.php',
            $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/tree')
        );
    }

    public function testUpdateRemoval()
    {
        $this->_assertContentsNotContain(
            'lib/Old.php',
            $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/remove')
        );
    }

    public function testUpdatePrune()
    {
        $this->assertContains(
            '<dir name="lib">
    <dir name="b">',
            (string) $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/remove')
        );
    }

    public function testUpdateOrder()
    {
        $this->assertContains(
            '<dir name="lib">
    <dir name="A">
     <file name="a.php" role="php" />
    </dir> <!-- /lib/A -->
    <dir name="b">
     <file name="a.php" role="php" />
    </dir> <!-- /lib/b -->
    <dir name="z">
     <file name="a.php" role="php" />
    </dir> <!-- /lib/z -->
    <file name="A.php" role="php" />
    <file name="R.php" role="php" />
    <file name="Stays.php" role="php">
      <tasks:replace from="@data_dir@" to="data_dir" type="pear-config" />
    </file>
    <file name="Z.php" role="php" />',
            (string) $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/order')
        );
    }

    public function testRole()
    {
        $xml = $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/simple-empty');
        $file = $this->_getContentsFile('lib/Stays.php', $xml);
        $this->assertEquals('php', $file->getAttribute('role'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUndefined()
    {
        $xml = $this->_getUpdatedContents(__DIR__ . '/../../fixture/horde/framework/simple');
        $xml->noSuchTaskHasBeenDefined();
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
        $xml->updateContents(
            new Horde_Pear_Package_Contents_List(
                new Horde_Pear_Package_Type_Horde($package)
            )
        );
        return $xml;
    }

    private function _getFixture()
    {
        return new Horde_Pear_Package_Xml(
            fopen(__DIR__ . '/../../fixture/horde/framework/simple/package.xml', 'r')
        );
    }

    private function _getEmptyNotesFixture()
    {
        return new Horde_Pear_Package_Xml(
            fopen(__DIR__ . '/../../fixture/horde/framework/notes/package.xml', 'r')
        );
    }
}
