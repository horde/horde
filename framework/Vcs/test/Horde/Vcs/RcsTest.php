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
        $this->assertFalse($this->vcs->hasFeature('branches'));
        $this->assertFalse($this->vcs->hasFeature('deleted'));
        $this->assertFalse($this->vcs->hasFeature('patchsets'));
        $this->assertFalse($this->vcs->hasFeature('snapshots'));
        $this->assertFalse($this->vcs->hasFeature('foo'));
    }

    public function testDirectory()
    {
        $dir = $this->vcs->getDirObject('');
        $this->assertInstanceOf('Horde_Vcs_Directory_Rcs', $dir);
        $files = $dir->queryFileList();
        $this->assertInternalType('array', $files);
        $this->assertCount(1, $files);
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $files[0]);
        $this->assertCount(1, $dir->queryFileList(true));
        $this->assertEquals(array(), $dir->getBranches());
    }

    public function testFile()
    {
        $dir = $this->vcs->getFileObject('foo');
        $this->assertInstanceOf('Horde_Vcs_File_Rcs', $dir);
    }

    public function testLog()
    {
        $dir = $this->vcs->getLogObject($this->vcs->getFileObject('foo'), '');
        $this->assertInstanceOf('Horde_Vcs_Log_Rcs', $dir);
    }
}
