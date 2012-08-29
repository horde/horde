<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/TestBase.php';

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
                        array('sourceroot' => 'file://' . __DIR__ . '/repos/svn')));
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
        $this->assertEquals(2, count($files));
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $files[0]);
        $this->assertEquals('file1', $files[0]->getFileName());
        $this->assertEquals('umläüte', $files[1]->getFileName());
        $this->assertEquals(2, count($dir->getFiles(true)));
        $this->assertEquals(array(), $dir->getBranches());

        $dir = $this->vcs->getDirectory('dir1');
        $this->assertInstanceOf('Horde_Vcs_Directory_Svn', $dir);
        $this->assertEquals(array(), $dir->getDirectories());
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
            'file://' . __DIR__ . '/repos/svn/file1',
            $file->getPath());
        $this->assertEquals('4', $file->getRevision());
        $this->assertEquals('1', $file->getPreviousRevision('2'));
        $this->assertEquals(3, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertEquals(array(), $file->getBranches());
        $this->assertFalse($file->isDeleted());

        /* Test sub-directory file. */
        $file = $this->vcs->getFile('dir1/file1_1');
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $file);
        $this->assertEquals('file1_1', $file->getFileName());
        $this->assertEquals('dir1/file1_1', $file->getSourcerootPath());
        $this->assertEquals(
            'file://' . __DIR__ . '/repos/svn/dir1/file1_1',
            $file->getPath());
        $this->assertEquals('1', $file->getRevision());
        $this->assertEquals(1, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertFalse($file->isDeleted());

        /* Test deleted file. */
        $file = $this->vcs->getFile('deletedfile1');
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $file);
        $this->assertEquals('deletedfile1', $file->getFileName());
        $this->assertEquals('deletedfile1', $file->getSourcerootPath());
        $this->assertEquals(
            'file://' . __DIR__ . '/repos/svn/deletedfile1',
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

        /* Test unicode file. */
        $file = $this->vcs->getFile('umläüte');
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $file);
        $this->assertEquals('umläüte', $file->getFileName());
        $this->assertEquals('umläüte', $file->getSourcerootPath());
        $this->assertEquals(
            'file://' . __DIR__ . '/repos/svn/umläüte',
            $file->getPath());
        $this->assertEquals('3', $file->getRevision());
        $this->assertEquals(1, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertEquals(array(), $file->getBranches());
        $this->assertFalse($file->isDeleted());
    }

    public function testLog()
    {
        $logs = $this->vcs->getFile('file1')->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('4', '2', '1'), array_keys($logs));
        $this->assertInstanceOf('Horde_Vcs_Log_Svn', $logs['2']);

        $log = $logs['2'];
        $this->assertEquals('2', $log->getRevision());
        $this->assertEquals(1322496080, $log->getDate());
        $this->assertEquals('jan', $log->getAuthor());
        $this->assertEquals('Commit 2nd version.', $log->getMessage());
        $this->assertEquals(array(), $log->getBranch());
        // Any way how to retrieve changes per patchset or file?
        $this->assertEquals('', $log->getChanges());
        $this->assertEquals(array(), $log->getTags());
        $this->assertEquals(array(), $log->getSymbolicBranches());
        // Any way how to retrieve changes per patchset or file?
        $this->assertEquals(
            array('file1' => array('status' => 'M')),
            $log->getFiles());
        $this->assertEquals(0, $log->getAddedLines());
        $this->assertEquals(0, $log->getDeletedLines());
        /*
        $this->assertEquals(
            array('file1' => array('added' => '1', 'deleted' => '1')),
            $log->getFiles());
        $this->assertEquals(1, $log->getAddedLines());
        $this->assertEquals(1, $log->getDeletedLines());
        */

        $log = $logs['1'];
        $this->assertEquals('1', $log->getRevision());
        $this->assertEquals(1322254316, $log->getDate());
        $this->assertEquals('jan', $log->getAuthor());
        $this->assertEquals('Add first files.', $log->getMessage());
        $this->assertEquals(array(), $log->getBranch());
        // Any way how to retrieve changes per patchset or file?
        $this->assertEquals('', $log->getChanges());
        $this->assertEquals(array(), $log->getTags());
        $this->assertEquals(array(), $log->getSymbolicBranches());
        // Any way how to retrieve changes per patchset or file?
        $this->assertEquals(
            array('dir1'         => array('status' => 'A'),
                  'dir1/file1_1' => array('status' => 'A'),
                  'file1'        => array('status' => 'A')),
            $log->getFiles());
        $this->assertEquals(0, $log->getAddedLines());
        $this->assertEquals(0, $log->getDeletedLines());
        /*
        $this->assertEquals(
            array('file1' => array('added' => '1', 'deleted' => '1')),
            $log->getFiles());
        $this->assertEquals(1, $log->getAddedLines());
        $this->assertEquals(1, $log->getDeletedLines());
        */

        $this->assertEquals(
            'Multiline commit message.

More message here
and here.',
            $logs['4']->getMessage());

        $logs = $this->vcs->getFile('umläüte')->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('3'), array_keys($logs));
        $this->assertInstanceOf('Horde_Vcs_Log_Svn', $logs['3']);
    }

    public function testLastLog()
    {
        $log = $this->vcs
            ->getFile('file1')
            ->getLastLog();
        $this->assertInstanceof('Horde_Vcs_QuickLog_Svn', $log);
        $this->assertEquals('4', $log->getRevision());
        $this->assertEquals(1332506387, $log->getDate());
        $this->assertEquals('jan', $log->getAuthor());
        $this->assertEquals('Multiline commit message.

More message here
and here.', $log->getMessage());
    }

    public function testPatchset()
    {
        $ps = $this->vcs->getPatchset(array('file' => 'file1'));
        $this->assertInstanceOf('Horde_Vcs_Patchset_Svn', $ps);
        $sets = $ps->getPatchsets();
        $this->assertInternalType('array', $sets);
        $this->assertEquals(3, count($sets));
        $this->assertEquals(array(4, 2, 1), array_keys($sets));
        $this->assertEquals(1, $sets[1]['revision']);
        $this->assertEquals(1322254316, $sets[1]['date']);
        $this->assertEquals('jan', $sets[1]['author']);
        $this->assertEquals('Add first files.', $sets[1]['log']);
        $this->assertEquals(
            array(array(
                'file'    => 'dir1',
                'from'    => null,
                'to'      => 1,
                'status'  => 1,
                /*'added'   => '1',
                'deleted' => '0'*/),
                  array(
                'file'    => 'dir1/file1_1',
                'from'    => null,
                'to'      => 1,
                'status'  => 1,
                /*'added'   => '1',
                'deleted' => '0'*/),
                  array(
                'file'    => 'file1',
                'from'    => null,
                'to'      => 1,
                'status'  => 1,
                /*'added'   => '1',
                'deleted' => '0',*/
            )),
            $sets[1]['members']);

        /* Test non-existant file. */
        try {
            $ps = $this->vcs->getPatchset(array('file' => 'foo'));
            $this->fail('Expected Horde_Vcs_Exception');
        } catch (Horde_Vcs_Exception $e) {
        }
    }
}
