<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/TestBase.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Vcs
 * @subpackage UnitTests
 */

class Horde_Vcs_RcsTest extends Horde_Vcs_TestBase
{
    public function setUp()
    {
        if (!self::$conf) {
            $this->markTestSkipped();
        }
        $this->vcs = Horde_Vcs::factory(
            'Rcs',
            array_merge(self::$conf,
                        array('sourceroot' => dirname(__FILE__) . '/repos/rcs')));
    }

    public function testFactory()
    {
        $this->assertInstanceOf('Horde_Vcs_Rcs', $this->vcs);

        /* Test features. */
        $this->assertFalse($this->vcs->hasFeature('branches'));
        $this->assertFalse($this->vcs->hasFeature('deleted'));
        $this->assertFalse($this->vcs->hasFeature('patchsets'));
        $this->assertFalse($this->vcs->hasFeature('snapshots'));
        $this->assertFalse($this->vcs->hasFeature('foo'));

        /* Test base object methods. */
        $this->assertTrue($this->vcs->isValidRevision('1.1'));
        $this->assertTrue($this->vcs->isValidRevision('4.2'));
        $this->assertTrue($this->vcs->isValidRevision('1.1.2.1'));
        $this->assertFalse($this->vcs->isValidRevision('1'));
    }

    public function testDirectory()
    {
        $dir = $this->vcs->getDirectory('');
        $this->assertInstanceOf('Horde_Vcs_Directory_Rcs', $dir);
        $files = $dir->getFiles();
        $this->assertInternalType('array', $files);
        $this->assertEquals(1, count($files));
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $files[0]);
        $this->assertEquals(1, count($dir->getFiles(true)));
        $this->assertEquals(array(), $dir->getBranches());

        /* Test non-existant directory. */
        try {
            $this->vcs->getDirectory('foo');
            $this->fail('Expected Horde_Vcs_Exception');
        } catch (Horde_Vcs_Exception $e) {
        }
    }

    public function testFile()
    {
        /* Test top-level file. */
        $file = $this->vcs->getFile('file1');
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $file);
        $this->assertEquals('file1', $file->getFileName());
        $this->assertEquals('file1', $file->getSourcerootPath());
        $this->assertEquals(dirname(__FILE__) . '/repos/rcs/file1',
                            $file->getPath());
        $this->assertEquals(dirname(__FILE__) . '/repos/rcs/file1,v',
                            $file->getFullPath());
        $this->assertEquals('1.2', $file->getRevision());
        $this->assertEquals('1.1', $file->getPreviousRevision('1.2'));
        $this->assertEquals(2, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertEquals(array(), $file->getBranches());
        $this->assertFalse($file->isDeleted());

        $log = $file->getLastLog();
        $this->assertInstanceOf('Horde_Vcs_Log_Rcs', $log);

        /* Test sub-directory file. */
        $file = $this->vcs->getFile('dir1/file1_1');
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $file);
        $this->assertEquals('file1_1', $file->getFileName());
        $this->assertEquals('dir1/file1_1', $file->getSourcerootPath());
        $this->assertEquals(
            dirname(__FILE__) . '/repos/rcs/dir1/file1_1',
            $file->getPath());
        $this->assertEquals(
            dirname(__FILE__) . '/repos/rcs/dir1/file1_1,v',
            $file->getFullPath());
        $this->assertEquals('1.1', $file->getRevision());
        $this->assertEquals(1, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertEquals(array(), $file->getBranches());
        $this->assertFalse($file->isDeleted());

        /* Test non-existant file. */
        $dir = $this->vcs->getFile('foo');
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $dir);
    }

    public function testLog()
    {
        $dir = $this->vcs->getLog($this->vcs->getFile('foo'), '');
        $this->assertInstanceOf('Horde_Vcs_Log_Rcs', $dir);
    }
}
