<?php
/**
 * Test the file based virtual file system.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Vfs
 */

/**
 * Test the file based virtual file system.
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Vfs
 */
class Horde_Vfs_FileTest extends PHPUnit_Framework_TestCase
{
    protected $_vfs;

    /**
     */
    public function setUp()
    {
        $this->_vfs = Horde_Vfs::factory('File', array(
            'vfsroot' => sys_get_temp_dir() . '/vfsfiletest'
        ));
    }

    public function testDeleteUnusalFileNames()
    {
        putenv('LANG=en_US.UTF-8');
        $file = '高&执&行&力&的&打&造.txt';
        $dir = '.horde/foo';
        $path = sys_get_temp_dir() . '/vfsfiletest/' . $dir . '/' . $file;
        $this->_vfs->writeData($dir, $file, 'some content');
        $this->assertFileExists($path);
        $this->assertStringEqualsFile($path, 'some content');
        $this->_vfs->delete($dir, $file);
        $this->assertThat(true, $this->logicalNot($this->fileExists($path)));
    }

    /**
     */
    public function testBug10583()
    {
        // Should not throw exception.
        $this->_vfs->listFolders();
    }
}
