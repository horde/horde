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

class Horde_Vcs_SvnTest extends Horde_Vcs_TestBase
{
    public function setUp()
    {
        if (!self::$conf) {
            $this->markTestSkipped();
        }
        $this->vcs = Horde_Vcs::factory(
            'Svn',
            array_merge(self::$conf,
                        array('sourceroot' => 'file://' . dirname(__FILE__) . '/repos/svn')));
    }

    public function testFactory()
    {
        $this->assertInstanceOf('Horde_Vcs_Svn', $this->vcs);

        /* Test features. */
        $this->assertFalse($this->vcs->hasFeature('branches'));
        $this->assertFalse($this->vcs->hasFeature('deleted'));
        $this->assertTrue($this->vcs->hasFeature('patchsets'));
        $this->assertFalse($this->vcs->hasFeature('snapshots'));
        $this->assertFalse($this->vcs->hasFeature('foo'));

        /* Test base object methods. */
        $this->assertTrue($this->vcs->isValidRevision('1'));
        $this->assertTrue($this->vcs->isValidRevision(1));
        $this->assertTrue($this->vcs->isValidRevision('42'));
        $this->assertTrue($this->vcs->isValidRevision(42));
        $this->assertFalse($this->vcs->isValidRevision('0'));
        $this->assertFalse($this->vcs->isValidRevision('1.1'));
    }

    public function testDirectory()
    {
        $dir = $this->vcs->getDirectory('');
        $this->assertInstanceOf('Horde_Vcs_Directory_Svn', $dir);
        $this->assertEquals(array('dir1'), $dir->getDirectories());
        $files = $dir->getFiles();
        $this->assertInternalType('array', $files);
        $this->assertEquals(1, count($files));
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $files[0]);
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
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $file);
        $this->assertEquals('file1', $file->getFileName());
        $this->assertEquals('file1', $file->getSourcerootPath());
        $this->assertEquals(
            'file:///home/jan/horde-git/framework/Vcs/test/Horde/Vcs/repos/svn/file1',
            $file->getPath());
        $this->assertEquals('2', $file->getRevision());
        $this->assertEquals('1', $file->getPreviousRevision('2'));
        $this->assertEquals(2, $file->revisionCount());
        //FIXME $this->assertEquals(array('tag1' => '2'),
        //FIXME                     $file->getTags());
        $this->assertEquals(array(), $file->getBranches());
        $this->assertFalse($file->isDeleted());

        $log = $file->getLastLog();
        $this->assertInstanceOf('Horde_Vcs_Log_Svn', $log);

        /* Test sub-directory file. */
        $file = $this->vcs->getFile('dir1/file1_1');
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $file);
        $this->assertEquals('file1_1', $file->getFileName());
        $this->assertEquals('dir1/file1_1', $file->getSourcerootPath());
        $this->assertEquals(
            'file:///home/jan/horde-git/framework/Vcs/test/Horde/Vcs/repos/svn/dir1/file1_1',
            $file->getPath());
        $this->assertEquals('1', $file->getRevision());
        $this->assertEquals(1, $file->revisionCount());
        //FIXME $this->assertEquals(array('tag1' => '1'),
        //FIXME     $file->getTags());
        $this->assertFalse($file->isDeleted());

        /* Test deleted file. */
        $file = $this->vcs->getFile('deletedfile1');
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $file);
        $this->assertEquals('deletedfile1', $file->getFileName());
        $this->assertEquals('deletedfile1', $file->getSourcerootPath());
        $this->assertEquals(
            'file:///home/jan/horde-git/framework/Vcs/test/Horde/Vcs/repos/svn/deletedfile1',
            $file->getPath());
        /* FIXME
        $this->assertEquals('2', $file->getRevision());
        $this->assertEquals('1', $file->getPreviousRevision('2'));
        $this->assertEquals(2, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertTrue($file->isDeleted());
        */

        /* Test non-existant file. */
        $file = $this->vcs->getFile('foo');
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $file);
    }

    public function testLog()
    {
        $log = $this->vcs->getLog($this->vcs->getFile('foo'), '');
        $this->assertInstanceOf('Horde_Vcs_Log_Svn', $log);
    }

    public function testPatchset()
    {
        $ps = $this->vcs->getPatchset(array('file' => 'foo'));
        $this->assertInstanceOf('Horde_Vcs_Patchset_Svn', $ps);
    }
}
