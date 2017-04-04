<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */

/**
 * Test the symlink ignore handler for package contents.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2012-2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */
class Horde_Pear_Unit_Package_Contents_Ignore_SymlinkTest
extends Horde_Pear_TestCase
{
    public function setUp()
    {
        $this->_file = tempnam(sys_get_temp_dir(), 'horde_pear_');
        file_put_contents($this->_file, '');
        $this->_link = tempnam(sys_get_temp_dir(), 'horde_pear_');
        unlink($this->_link);
        if (!@symlink($this->_file, $this->_link)) {
            unlink($this->_file);
            $this->markTestSkipped('Unable to create symbolic link');
        }
    }

    public function tearDown()
    {
        unlink($this->_link);
        unlink($this->_file);
    }

    public function testFile()
    {
        $this->_checkNotIgnored($this->_file);
    }

    public function testSymlink()
    {
        $this->_checkIgnored($this->_link);
    }

    private function _checkIgnored($file)
    {
        $this->assertTrue(
            $this->_getIgnore()->isIgnored(new SplFileInfo($file))
        );
    }

    private function _checkNotIgnored($file)
    {
        $this->assertFalse(
            $this->_getIgnore()->isIgnored(new SplFileInfo($file))
        );
    }

    private function _getIgnore()
    {
        return new Horde_Pear_Package_Contents_Ignore_Symlink();
    }
}
