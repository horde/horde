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
                        array('sourceroot' => __DIR__ . '/repos/rcs')));
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
        $this->assertEquals(2, count($files));
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $files[0]);
        $this->assertEquals('umläüte', $files[0]->getFileName());
        $this->assertEquals('file1', $files[1]->getFileName());
        $this->assertEquals(2, count($dir->getFiles(true)));
        $this->assertEquals(array(), $dir->getBranches());

        $dir = $this->vcs->getDirectory('dir1');
        $this->assertInstanceOf('Horde_Vcs_Directory_Rcs', $dir);
        $this->assertEquals(array(), $dir->getDirectories());
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
        $this->assertEquals(__DIR__ . '/repos/rcs/file1',
                            $file->getPath());
        $this->assertEquals(__DIR__ . '/repos/rcs/file1,v',
                            $file->getFullPath());
        $this->assertEquals('1.3', $file->getRevision());
        $this->assertEquals('1.1', $file->getPreviousRevision('1.2'));
        $this->assertEquals(3, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertEquals(array(), $file->getBranches());
        $this->assertFalse($file->isDeleted());

        /* Test sub-directory file. */
        $file = $this->vcs->getFile('dir1/file1_1');
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $file);
        $this->assertEquals('file1_1', $file->getFileName());
        $this->assertEquals('dir1/file1_1', $file->getSourcerootPath());
        $this->assertEquals(
            __DIR__ . '/repos/rcs/dir1/file1_1',
            $file->getPath());
        $this->assertEquals(
            __DIR__ . '/repos/rcs/dir1/file1_1,v',
            $file->getFullPath());
        $this->assertEquals('1.1', $file->getRevision());
        $this->assertEquals(1, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertEquals(array(), $file->getBranches());
        $this->assertFalse($file->isDeleted());

        /* Test non-existant file. */
        try {
            $dir = $this->vcs->getFile('foo');
            $this->fail('Expected Horde_Vcs_Exception');
        } catch (Horde_Vcs_Exception $e) {
        }
    }

    public function testUnicodeFile()
    {
        if (!setlocale(LC_ALL, 'de_DE.UTF-8')) {
            $this->skipTest('Cannot set de_DE locale');
        }

        /* Test unicode file. */
        $file = $this->vcs->getFile('umläüte');
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $file);
        $this->assertEquals('umläüte', $file->getFileName());
        $this->assertEquals('umläüte', $file->getSourcerootPath());
        $this->assertEquals(__DIR__ . '/repos/rcs/umläüte',
                            $file->getPath());
        $this->assertEquals(__DIR__ . '/repos/rcs/umläüte,v',
                            $file->getFullPath());
        $this->assertEquals('1.1', $file->getRevision());
        $this->assertEquals(1, $file->revisionCount());
        $this->assertEquals(array(), $file->getTags());
        $this->assertEquals(array(), $file->getBranches());
        $this->assertFalse($file->isDeleted());
    }

    public function testLog()
    {
        $logs = $this->vcs->getFile('file1')->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('1.3', '1.2', '1.1'), array_keys($logs));
        $this->assertInstanceOf('Horde_Vcs_Log_Rcs', $logs['1.2']);

        $log = $logs['1.2'];
        $this->assertEquals('1.2', $log->getRevision());
        $this->assertEquals(1322495969, $log->getDate());
        $this->assertEquals('jan', $log->getAuthor());
        $this->assertEquals('Commit 2nd version.', $log->getMessage());
        $this->assertEquals(array(), $log->getBranch());
        $this->assertEquals('+1 -1', $log->getChanges());
        $this->assertEquals(array(), $log->getTags());
        $this->assertEquals(array(), $log->getSymbolicBranches());
        $this->assertEquals(
            array('file1' => array('added' => '1', 'deleted' => '1')),
            $log->getFiles());
        $this->assertEquals(1, $log->getAddedLines());
        $this->assertEquals(1, $log->getDeletedLines());

        $log = $logs['1.1'];
        $this->assertEquals('1.1', $log->getRevision());
        $this->assertEquals(1322254390, $log->getDate());
        $this->assertEquals(
            'Add first files.',
            $log->getMessage());
        $this->assertEquals(array(), $log->getBranch());
        $this->assertEquals(array(), $log->getTags());

        $this->assertEquals(
            'Multiline commit message.

More message here
and here.',
            $logs['1.3']->getMessage());

        $logs = $this->vcs->getFile('umläüte')->getLog();
        $this->assertInternalType('array', $logs);
        $this->assertEquals(array('1.1'), array_keys($logs));
        $this->assertInstanceOf('Horde_Vcs_Log_Rcs', $logs['1.1']);
    }

    public function testLastLog()
    {
        $log = $this->vcs
            ->getFile('file1')
            ->getLastLog();
        $this->assertInstanceof('Horde_Vcs_QuickLog_Rcs', $log);
        $this->assertEquals('1.3', $log->getRevision());
        $this->assertEquals(1332506787, $log->getDate());
        $this->assertEquals('jan', $log->getAuthor());
        $this->assertEquals('Multiline commit message.

More message here
and here.', $log->getMessage());
    }
}
