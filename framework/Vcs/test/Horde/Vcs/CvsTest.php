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

class Horde_Vcs_CvsTest extends Horde_Vcs_TestBase
{
    public function setUp()
    {
        if (!self::$conf) {
            $this->markTestSkipped();
        }
        $this->vcs = Horde_Vcs::factory(
            'Cvs',
            array_merge(self::$conf,
                        array('sourceroot' => dirname(__FILE__) . '/repos/cvs')));
    }

    public function testFactory()
    {
        $this->assertInstanceOf('Horde_Vcs_Cvs', $this->vcs);

        /* Test features. */
        $this->assertTrue($this->vcs->hasFeature('branches'));
        $this->assertTrue($this->vcs->hasFeature('deleted'));
        $this->assertEquals(isset(self::$conf['paths']['cvsps']),
                            $this->vcs->hasFeature('patchsets'));
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
        $dir = $this->vcs->getDirectory('module');
        $this->assertInstanceOf('Horde_Vcs_Directory_Cvs', $dir);
        $this->assertEquals(array('dir1'), $dir->getDirectories());
        $files = $dir->getFiles();
        $this->assertInternalType('array', $files);
        $this->assertEquals(1, count($files));
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $files[0]);
        $this->assertEquals(1, count($dir->getFiles(true)));
        $this->assertEquals(array('HEAD'), $dir->getBranches());
        // If we ever implement branch listing on directories:
        // $this->assertEquals(array('HEAD', 'branch1'), $dir->getBranches());

        $dir = $this->vcs->getDirectory('module/dir1');
        $this->assertInstanceOf('Horde_Vcs_Directory_Cvs', $dir);
        $this->assertEquals(array(), $dir->getDirectories());
        $files = $dir->getFiles();
        $this->assertInternalType('array', $files);
        $this->assertEquals(1, count($files));
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $files[0]);
        $this->assertEquals(1, count($dir->getFiles(true)));
        $this->assertEquals(array('HEAD'), $dir->getBranches());
        // If we ever implement branch listing on directories:
        // $this->assertEquals(array('HEAD', 'branch1'), $dir->getBranches());

        /* Test deleted files. */
        $dir = $this->vcs->getDirectory('module', array('showattic' => true));
        $this->assertEquals(1, count($dir->getFiles()));
        $this->assertEquals(2, count($dir->getFiles(true)));

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
        $file = $this->vcs->getFile('module/file1');
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $file);
        $this->assertEquals('file1', $file->getFileName());
        $this->assertEquals('module/file1', $file->getSourcerootPath());
        $this->assertEquals(dirname(__FILE__) . '/repos/cvs/module/file1',
                            $file->getPath());
        $this->assertEquals(dirname(__FILE__) . '/repos/cvs/module/file1,v',
                            $file->getFullPath());
        $this->assertEquals('1.2', $file->getRevision());
        $this->assertEquals('1.1', $file->getPreviousRevision('1.2'));
        $this->assertEquals('1.1', $file->getPreviousRevision('1.1.2.1'));
        $this->assertEquals(3, $file->revisionCount());
        $this->assertEquals(array('tag1' => '1.2'),
                            $file->getTags());
        $this->assertEquals(array('HEAD' => '1.2', 'branch1' => '1.1.2.1'),
                            $file->getBranches());
        $this->assertFalse($file->isDeleted());

        $log = $file->getLastLog();
        $this->assertInstanceOf('Horde_Vcs_Log_Cvs', $log);

        /* Test sub-directory file. */
        $file = $this->vcs->getFile('module/dir1/file1_1');
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $file);
        $this->assertEquals('file1_1', $file->getFileName());
        $this->assertEquals('module/dir1/file1_1', $file->getSourcerootPath());
        $this->assertEquals(
            dirname(__FILE__) . '/repos/cvs/module/dir1/file1_1',
            $file->getPath());
        $this->assertEquals(
            dirname(__FILE__) . '/repos/cvs/module/dir1/file1_1,v',
            $file->getFullPath());
        $this->assertEquals('1.1', $file->getRevision());
        $this->assertEquals(1, $file->revisionCount());
        $this->assertEquals(array('tag1' => '1.1'),
            $file->getTags());
        $this->assertEquals(array('HEAD' => '1.1'), $file->getBranches());
        $this->assertFalse($file->isDeleted());

        /* Test deleted file. */
        $file = $this->vcs->getFile('module/deletedfile1');
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $file);
        $this->assertEquals('deletedfile1', $file->getFileName());
        $this->assertEquals(
            'module/Attic/deletedfile1',
            $file->getSourcerootPath());
        $this->assertEquals(
            dirname(__FILE__) . '/repos/cvs/module/Attic/deletedfile1',
            $file->getPath());
        $this->assertEquals(
            dirname(__FILE__) . '/repos/cvs/module/Attic/deletedfile1,v',
            $file->getFullPath());
        $this->assertEquals('1.2', $file->getRevision());
        $this->assertEquals('1.1', $file->getPreviousRevision('1.2'));
        $this->assertEquals(2, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertTrue($file->isDeleted());

        /* Test non-existant file. */
        try {
            $file = $this->vcs->getFile('foo');
            $this->fail('Expected Horde_Vcs_Exception');
        } catch (Horde_Vcs_Exception $e) {
        }
    }

    public function testLog()
    {
        $logs = $this->vcs->getFile('module/file1')->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('1.2', '1.1', '1.1.2.1'), array_keys($logs));
        $this->assertInstanceOf('Horde_Vcs_Log_Cvs', $logs['1.2']);
        $log = $logs['1.2'];
        $this->assertEquals('1.2', $log->getRevision());
        $this->assertEquals(1322495647, $log->getDate());
        $this->assertEquals('jan', $log->getAuthor());
        $this->assertEquals(
            'Commit 2nd version to HEAD branch.',
            $log->getMessage());
        $this->assertEquals(array('HEAD'), $log->getBranch());
        $this->assertEquals('+1 -1', $log->getChanges());
        $this->assertEquals(array('tag1'), $log->getTags());
        $this->assertEquals(array(), $log->getSymbolicBranches());
        $this->assertEquals(
            array('module/file1' => array('added' => '1', 'deleted' => '1')),
            $log->getFiles());
        $this->assertEquals(1, $log->getAddedLines());
        $this->assertEquals(1, $log->getDeletedLines());
    }

    public function testPatchset()
    {
        if (!$this->vcs->hasFeature('patchsets')) {
            $this->markTestSkipped('cvsps is not installed');
        }
        try {
            $ps = $this->vcs->getPatchset(array('file' => 'foo'));
            $this->fail('Expected Horde_Vcs_Exception');
        } catch (Horde_Vcs_Exception $e) {
        }
        //$this->assertInstanceOf('Horde_Vcs_Patchset_Cvs', $ps);
    }
}
