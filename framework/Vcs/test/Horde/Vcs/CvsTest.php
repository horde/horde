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

class Horde_Vcs_CvsTest extends Horde_Vcs_TestBase
{
    public function setUp()
    {
        if (!self::$conf) {
            $this->markTestSkipped();
        }
        $conf = self::$conf;
        $conf['paths']['cvsps_home'] = Horde_Util::createTempDir(
            true, $conf['paths']['cvsps_home']);
        $conf['sourceroot'] = __DIR__ . '/repos/cvs';
        $this->vcs = Horde_Vcs::factory('Cvs', $conf);
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
        $this->assertEquals(2, count($files));
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $files[0]);
        $this->assertEquals('umläüte', $files[0]->getFileName());
        $this->assertEquals('file1', $files[1]->getFileName());
        $this->assertEquals(2, count($dir->getFiles(true)));
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
        $this->assertEquals(2, count($dir->getFiles()));
        $this->assertEquals(3, count($dir->getFiles(true)));

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
        $this->assertEquals(__DIR__ . '/repos/cvs/module/file1',
                            $file->getPath());
        $this->assertEquals(__DIR__ . '/repos/cvs/module/file1,v',
                            $file->getFullPath());
        $this->assertEquals('1.3', $file->getRevision());
        $this->assertEquals('1.1', $file->getPreviousRevision('1.2'));
        $this->assertEquals('1.1', $file->getPreviousRevision('1.1.2.1'));
        $this->assertEquals(4, $file->revisionCount());
        $this->assertEquals(array('tag1' => '1.2'),
                            $file->getTags());
        $this->assertEquals(array('HEAD' => '1.3', 'branch1' => '1.1.2.1'),
                            $file->getBranches());
        $this->assertFalse($file->isDeleted());

        $file = $this->vcs->getFile('module/file1', array('branch' => 'HEAD'));
        $this->assertEquals(array('HEAD' => '1.3', 'branch1' => '1.1.2.1'),
                            $file->getBranches());

        $file = $this->vcs->getFile('module/file1', array('branch' => 'branch1'));
        $this->assertEquals(array('HEAD' => '1.3', 'branch1' => '1.1.2.1'),
                            $file->getBranches());

        /* Test sub-directory file. */
        $file = $this->vcs->getFile('module/dir1/file1_1');
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $file);
        $this->assertEquals('file1_1', $file->getFileName());
        $this->assertEquals('module/dir1/file1_1', $file->getSourcerootPath());
        $this->assertEquals(
            __DIR__ . '/repos/cvs/module/dir1/file1_1',
            $file->getPath());
        $this->assertEquals(
            __DIR__ . '/repos/cvs/module/dir1/file1_1,v',
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
            __DIR__ . '/repos/cvs/module/Attic/deletedfile1',
            $file->getPath());
        $this->assertEquals(
            __DIR__ . '/repos/cvs/module/Attic/deletedfile1,v',
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

        /* Test unicode file. */
        $file = $this->vcs->getFile('module/umläüte');
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $file);
        $this->assertEquals('umläüte', $file->getFileName());
        $this->assertEquals('module/umläüte', $file->getSourcerootPath());
        $this->assertEquals(__DIR__ . '/repos/cvs/module/umläüte',
                            $file->getPath());
        $this->assertEquals(__DIR__ . '/repos/cvs/module/umläüte,v',
                            $file->getFullPath());
        $this->assertEquals('1.1', $file->getRevision());
        $this->assertEquals(1, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertEquals(array('HEAD' => '1.1'), $file->getBranches());
        $this->assertFalse($file->isDeleted());
    }

    public function testLog()
    {
        $logs = $this->vcs->getFile('module/file1')->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('1.3', '1.2', '1.1', '1.1.2.1'),
                            array_keys($logs));
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

        $log = $logs['1.1'];
        $this->assertEquals('1.1', $log->getRevision());
        $this->assertEquals(1322254184, $log->getDate());
        $this->assertEquals(
            'Add first files.',
            $log->getMessage());
        $this->assertEquals(array('HEAD'), $log->getBranch());
        $this->assertEquals(array(), $log->getTags());

        $this->assertEquals(
            'Multiline commit message.

More message here
and here.',
            $logs['1.3']->getMessage());

        $log = $logs['1.1.2.1'];
        $this->assertEquals('1.1.2.1', $log->getRevision());
        $this->assertEquals(1322495667, $log->getDate());
        $this->assertEquals(
            'Commit 2nd version to branch1 branch.',
            $log->getMessage());
        $this->assertEquals(array('branch1'), $log->getBranch());
        $this->assertEquals(array(), $log->getTags());

        $logs = $this->vcs->getFile('module/file1', array('branch' => 'HEAD'))
            ->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('1.3', '1.2', '1.1'), array_keys($logs));

        $logs = $this->vcs->getFile('module/file1', array('branch' => 'branch1'))
            ->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('1.1', '1.1.2.1'), array_keys($logs));

        $logs = $this->vcs->getFile('module/umläüte')->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('1.1'), array_keys($logs));
        $this->assertInstanceOf('Horde_Vcs_Log_Cvs', $logs['1.1']);
    }

    public function testLastLog()
    {
        $log = $this->vcs
            ->getFile('module/file1')
            ->getLastLog();
        $this->assertInstanceof('Horde_Vcs_QuickLog_Cvs', $log);
        $this->assertEquals('1.3', $log->getRevision());
        $this->assertEquals(1332506364, $log->getDate());
        $this->assertEquals('jan', $log->getAuthor());
        $this->assertEquals(
            'Multiline commit message.

More message here
and here.',
            $log->getMessage());

        $log = $this->vcs
            ->getFile('module/file1', array('branch' => 'branch1'))
            ->getLastLog();
        $this->assertInstanceof('Horde_Vcs_QuickLog_Cvs', $log);
        $this->assertEquals('1.1.2.1', $log->getRevision());
        $this->assertEquals(1322495667, $log->getDate());
        $this->assertEquals('jan', $log->getAuthor());
        $this->assertEquals(
            'Commit 2nd version to branch1 branch.',
            $log->getMessage());
    }

    public function testPatchset()
    {
        if (!$this->vcs->hasFeature('patchsets')) {
            $this->markTestSkipped('cvsps is not installed');
        }

        date_default_timezone_set('Europe/Berlin');
        $ps = $this->vcs->getPatchset(array('file' => 'module/file1'));
        $this->assertInstanceOf('Horde_Vcs_Patchset_Cvs', $ps);
        $sets = $ps->getPatchsets();
        $this->assertInternalType('array', $sets);
        $this->assertEquals(6, count($sets));
        $this->assertEquals(array(1, 2, 3, 4, 5, 7), array_keys($sets));
        $this->assertEquals(1, $sets[1]['revision']);
        $this->assertEquals(1322254184, $sets[1]['date']);
        $this->assertEquals('jan', $sets[1]['author']);
        $this->assertEquals('Add first files.', $sets[1]['log']);
        $this->assertEquals(
            array(array(
                'file'    => 'file1',
                'from'    => null,
                'status'  => 1,
                'to'      => '1.1',
                /*'added'   => '1',
                  'deleted' => '0'*/),
                  array(
                'file'    => 'dir1/file1_1',
                'from'    => null,
                'status'  => 1,
                'to'      => '1.1',
                /*'added'   => '1',
                'deleted' => '0'*/)),
            $sets[1]['members']);

        /* Test non-existant file. */
        try {
            $ps = $this->vcs->getPatchset(array('file' => 'foo'));
            $this->fail('Expected Horde_Vcs_Exception');
        } catch (Horde_Vcs_Exception $e) {
        }
    }
}
