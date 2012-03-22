<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Test the file based virtual file system.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Vfs_FileTest extends Horde_Vfs_Test_Base
{

    public function testListEmpty()
    {
        $this->_listEmpty();
    }

    public function testCreateFolder()
    {
        $this->_createFolderStructure();
    }

    /**
     * @depends testCreateFolder
     */
    public function testWriteData()
    {
        $this->_writeData();
    }

    /**
     * @depends testCreateFolder
     */
    public function testWrite()
    {
        $this->_write();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testRead()
    {
        $this->_read();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testReadFile()
    {
        $this->_readFile();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testReadStream()
    {
        $this->_readStream();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testReadByteRange()
    {
        $this->_readByteRange();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testSize()
    {
        $this->_size();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testFolderSize()
    {
        $this->_folderSize();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testVfsSize()
    {
        $this->_vfsSize();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testCopy()
    {
        $this->_copy();
    }

    /**
     * @depends testCopy
     */
    public function testRename()
    {
        $this->_rename();
    }

    /**
     * @depends testRename
     */
    public function testMove()
    {
        $this->_move();
    }

    /**
     * @depends testMove
     */
    public function testDeleteFile()
    {
        $this->_deleteFile();
    }

    /**
     * @depends testMove
     */
    public function testDeleteFolder()
    {
        $this->_deleteFolder();
    }

    /**
     * @depends testMove
     */
    public function testEmptyFolder()
    {
        $this->_emptyFolder();
    }

    /**
     * @depends testMove
     */
    public function testQuota()
    {
        $this->_quota();
    }

    /**
     * @depends testQuota
     */
    public function testListFolder()
    {
        $this->_listFolder();
    }

    public function testDeleteUnusalFileNames()
    {
        putenv('LANG=en_US.UTF-8');
        $file = '高&执&行&力&的&打&造.txt';
        $dir = '.horde/foo';
        $path = sys_get_temp_dir() . '/vfsfiletest/' . $dir . '/' . $file;
        self::$vfs->writeData($dir, $file, 'some content', true);
        $this->assertFileExists($path);
        $this->assertStringEqualsFile($path, 'some content');
        self::$vfs->delete($dir, $file);
        $this->assertThat(true, $this->logicalNot($this->fileExists($path)));
    }

    static public function setUpBeforeClass()
    {
        self::$vfs = Horde_Vfs::factory('File', array(
            'vfsroot' => sys_get_temp_dir() . '/vfsfiletest'
        ));
    }

    static public function tearDownAfterClass()
    {
        system('rm -r ' . sys_get_temp_dir() . '/vfsfiletest');
        parent::tearDownAfterClass();
    }
}
