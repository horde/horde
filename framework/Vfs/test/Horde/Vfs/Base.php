<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Vfs_Test_Base extends Horde_Test_Case
{
    protected static $vfs;

    protected function _listEmpty()
    {
        $this->assertEquals(array(), self::$vfs->listFolder(''));
        $this->assertEquals(array(), self::$vfs->listFolder('/'));
    }

    protected function _createFolderStructure()
    {
        self::$vfs->createFolder('', 'test');
        self::$vfs->createFolder('test', 'dir1');
        self::$vfs->createFolder('test', 'dir2');
    }

    protected function _writeData()
    {
        self::$vfs->writeData('test', 'file1', 'content1');
        self::$vfs->writeData('test/dir1', 'file1', 'content1_1');
        self::$vfs->writeData('test/dir3', 'file1', 'content3_1', true);
        try {
            self::$vfs->writeData('test/dir4', 'file1', 'content4_1');
            $this->fail('Missing directory should throw an exception unless $autocreate is set');
        } catch (Horde_Vfs_Exception $e) {
        }
    }

    protected function _write()
    {
        self::$vfs->write('test/dir1', 'file2', __FILE__);
        self::$vfs->write('test/dir3', 'file2', __FILE__, true);
        try {
            self::$vfs->write('test/dir4', 'file2', __FILE__);
            $this->fail('Missing directory should throw an exception unless $autocreate is set');
        } catch (Horde_Vfs_Exception $e) {
        }
    }

    protected function _read()
    {
        $this->assertEquals('content1', self::$vfs->read('test', 'file1'));
        $this->assertEquals('content1_1', self::$vfs->read('test/dir1', 'file1'));
        $this->assertEquals('content3_1', self::$vfs->read('test/dir3', 'file1'));
        $this->assertEquals(file_get_contents(__FILE__), self::$vfs->read('test/dir1', 'file2'));
        $this->assertEquals(file_get_contents(__FILE__), self::$vfs->read('test/dir3', 'file2', __FILE__));
    }

    protected function _readFile()
    {
        $this->assertFileEquals(__FILE__, self::$vfs->readFile('test/dir1', 'file2'));
        $this->assertFileEquals(__FILE__, self::$vfs->readFile('test/dir3', 'file2', __FILE__));
    }

    protected function _listFolder()
    {
        $this->assertEquals(array('test'), array_keys(self::$vfs->listFolder('')));
        $this->assertEquals(array('test'), array_keys(self::$vfs->listFolder('/')));
    }

    public static function tearDownAfterClass()
    {
        self::$vfs = null;
    }
}