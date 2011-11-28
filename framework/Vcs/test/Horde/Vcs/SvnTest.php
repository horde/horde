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
        //$this->assertTrue($this->vcs->hasFeature('branches'));
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
        $dir = $this->vcs->getDirObject('');
        $this->assertInstanceOf('Horde_Vcs_Directory_Svn', $dir);
        $this->assertEquals(array('dir1'), $dir->queryDirList());
        $files = $dir->queryFileList();
        $this->assertInternalType('array', $files);
        $this->assertCount(1, $files);
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $files[0]);
        $this->assertCount(1, $dir->queryFileList(true));
        $this->assertEquals(array(), $dir->getBranches());

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
        $this->assertInstanceOf('Horde_Vcs_File_Svn', $file);
    }

    public function testLog()
    {
        $log = $this->vcs->getLogObject($this->vcs->getFileObject('foo'), '');
        $this->assertInstanceOf('Horde_Vcs_Log_Svn', $log);
    }

    public function testPatchset()
    {
        $ps = $this->vcs->getPatchsetObject(array('file' => 'foo'));
        $this->assertInstanceOf('Horde_Vcs_Patchset_Svn', $ps);
    }
}
