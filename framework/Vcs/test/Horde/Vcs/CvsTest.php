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
        $this->assertTrue($this->vcs->hasFeature('branches'));
        $this->assertTrue($this->vcs->hasFeature('deleted'));
        $this->assertEquals(isset(self::$conf['paths']['cvsps']),
                            $this->vcs->hasFeature('patchsets'));
        $this->assertFalse($this->vcs->hasFeature('snapshots'));
        $this->assertFalse($this->vcs->hasFeature('foo'));
    }

    public function testDirectory()
    {
        $dir = $this->vcs->getDirObject('module');
        $this->assertInstanceOf('Horde_Vcs_Directory_Cvs', $dir);
        $this->assertEquals(array('dir1'), $dir->queryDirList());
        $files = $dir->queryFileList();
        $this->assertInternalType('array', $files);
        $this->assertCount(1, $files);
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $files[0]);
        $this->assertCount(1, $dir->queryFileList(true));
        $this->assertEquals(array('HEAD'), $dir->getBranches());
        // If we ever implement branch listing on directories:
        // $this->assertEquals(array('HEAD', 'branch1'), $dir->getBranches());

        /* Test deleted files. */
        $dir = $this->vcs->getDirObject('module', array('showattic' => true));
        $this->assertCount(1, $dir->queryFileList());
        $this->assertCount(2, $dir->queryFileList(true));

        /* Test non-existant directory. */
        try {
            $this->vcs->getDirObject('foo');
            $this->fail('Expected Horde_Vcs_Exception');
        } catch (Horde_Vcs_Exception $e) {
        }
    }

    public function testFile()
    {
        $file = $this->vcs->getFileObject('foo');
        $this->assertInstanceOf('Horde_Vcs_File_Cvs', $file);
    }

    public function testLog()
    {
        $log = $this->vcs->getLogObject($this->vcs->getFileObject('foo'), '');
        $this->assertInstanceOf('Horde_Vcs_Log_Cvs', $log);
    }

    public function testPatchset()
    {
        if (!$this->vcs->hasFeature('patchsets')) {
            $this->markTestSkipped('cvsps is not installed');
        }
        try {
            $ps = $this->vcs->getPatchsetObject(array('file' => 'foo'));
            $this->fail('Expected Horde_Vcs_Exception');
        } catch (Horde_Vcs_Exception $e) {
        }
        //$this->assertInstanceOf('Horde_Vcs_Patchset_Cvs', $ps);
    }
}
