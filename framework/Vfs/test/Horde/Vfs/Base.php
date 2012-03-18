<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

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

    public static function tearDownAfterClass()
    {
        self::$vfs = null;
    }

    protected function _listEmpty()
    {
        $this->assertEquals(array(), self::$vfs->listFolder(''));
        $this->assertEquals(array(), self::$vfs->listFolder('/'));
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *   dir2/
     */
    protected function _createFolderStructure()
    {
        self::$vfs->createFolder('', 'test');
        self::$vfs->createFolder('test', 'dir1');
        self::$vfs->createFolder('test', 'dir2');
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *   dir2/
     *   dir3/
     *     file1: content3_1
     *   file1: content1
     */
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

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *   dir3/
     *     file1: content3_1
     *     file2: __FILE__
     *   file1: content1
     */
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

    protected function _readStream()
    {
        $this->assertEquals(
            file_get_contents(__FILE__),
            stream_get_contents(self::$vfs->readStream('test/dir1', 'file2')));
        $this->assertEquals(
            file_get_contents(__FILE__),
            stream_get_contents(self::$vfs->readStream('test/dir3', 'file2')));
    }

    protected function _readByteRange()
    {
        $offset = 1;
        $this->assertEquals('on', self::$vfs->readByteRange('test', 'file1', $offset, 2, $remain));
        $this->assertEquals(3, $offset);
        $this->assertEquals(5, $remain);
        $offset++;
        $this->assertEquals('en', self::$vfs->readByteRange('test', 'file1', $offset, 2, $remain));
        $this->assertEquals(6, $offset);
        $this->assertEquals(2, $remain);
        $this->assertEquals('t1', self::$vfs->readByteRange('test', 'file1', $offset, -1, $remain));
    }

    protected function _size()
    {
        $this->assertEquals(8, self::$vfs->size('test', 'file1'));
        $this->assertEquals(10, self::$vfs->size('test/dir1', 'file1'));
        $this->assertEquals(10, self::$vfs->size('test/dir3', 'file1'));
        $this->assertEquals(filesize(__FILE__), self::$vfs->size('test/dir1', 'file2'));
        $this->assertEquals(filesize(__FILE__), self::$vfs->size('test/dir3', 'file2', __FILE__));
    }

    protected function _folderSize()
    {
        $this->assertEquals(28 + 2 * filesize(__FILE__), self::$vfs->getFolderSize('test'));
        $this->assertEquals(10 + filesize(__FILE__), self::$vfs->getFolderSize('test/dir1'));
        $this->assertEquals(10 + filesize(__FILE__), self::$vfs->getFolderSize('test/dir3'));
    }

    protected function _vfsSize()
    {
        $this->assertEquals(28 + 2 * filesize(__FILE__), self::$vfs->getVFSSize());
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *   dir3/
     *     file1: content3_1
     *     file2: __FILE__
     *   dir4/
     *     file1: content1_1
     *   file1: content1
     */
    protected function _copy()
    {
        self::$vfs->copy('test/dir1', 'file1', 'test/dir4', true);
        $this->assertTrue(self::$vfs->exists('test/dir1', 'file1'));
        $this->assertTrue(self::$vfs->exists('test/dir4', 'file1'));
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *   dir3/
     *     file1: content3_1
     *     file2: __FILE__
     *     file3: content1_1
     *   dir4/
     *   file1: content1
     */
    protected function _rename()
    {
        self::$vfs->rename('test/dir4', 'file1', 'test/dir4', 'file2');
        $this->assertFalse(self::$vfs->exists('test/dir4', 'file1'));
        $this->assertTrue(self::$vfs->exists('test/dir4', 'file2'));
        self::$vfs->rename('test/dir4', 'file2', 'test/dir3', 'file3');
        $this->assertFalse(self::$vfs->exists('test/dir4', 'file2'));
        $this->assertTrue(self::$vfs->exists('test/dir3', 'file3'));
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *     file3: content1_1
     *   dir3/
     *     file1: content3_1
     *     file2: __FILE__
     *   dir4/
     *   file1: content1
     */
    protected function _move()
    {
        self::$vfs->move('test/dir3', 'file3', 'test/dir2');
        $this->assertFalse(self::$vfs->exists('test/dir3', 'file3'));
        $this->assertTrue(self::$vfs->exists('test/dir2', 'file3'));
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *   dir3/
     *     file1: content3_1
     *     file2: __FILE__
     *   dir4/
     *   file1: content1
     */
    protected function _deleteFile()
    {
        $this->assertTrue(self::$vfs->exists('test/dir2', 'file3'));
        self::$vfs->deleteFile('test/dir2', 'file3');
        $this->assertFalse(self::$vfs->exists('test/dir2', 'file3'));
        $this->assertFalse(self::$vfs->exists('test/dir4', 'file2'));
        try {
            self::$vfs->deleteFile('test/dir4', 'file2');
            $this->fail('Missing file should throw an exception');
        } catch (Horde_Vfs_Exception $e) {
        }
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *   file1: content1
     */
    protected function _deleteFolder()
    {
        $this->assertTrue(self::$vfs->exists('test', 'dir4'));
        self::$vfs->deleteFolder('test', 'dir4');
        $this->assertFalse(self::$vfs->exists('test', 'dir4'));
        $this->assertTrue(self::$vfs->exists('test', 'dir3'));
        try {
            self::$vfs->deleteFolder('test', 'dir3');
            $this->fail('Non-empty folder should throw an exception unless $recursive is set');
        } catch (Horde_Vfs_Exception $e) {
        }
        $this->assertTrue(self::$vfs->exists('test', 'dir3'));
        self::$vfs->deleteFolder('test', 'dir3', true);
        $this->assertFalse(self::$vfs->exists('test', 'dir3'));
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *   file1: content1
     */
    protected function _emptyFolder()
    {
        self::$vfs->copy('test/dir1', 'file1', 'test/dir2');
        self::$vfs->copy('test/dir1', 'file2', 'test/dir2');
        self::$vfs->createFolder('test/dir2', 'dir2_1');
        $this->assertEquals(
            array('dir2_1', 'file1', 'file2'),
            array_keys($this->_sort(self::$vfs->listFolder('test/dir2'))));
        self::$vfs->emptyFolder('test/dir2');
        $this->assertFalse(self::$vfs->exists('test/dir2', 'file1'));
        $this->assertFalse(self::$vfs->exists('test/dir2', 'file2'));
        $this->assertFalse(self::$vfs->exists('test/dir2', 'dir2_1'));
        $this->assertTrue(self::$vfs->exists('test', 'dir2'));
        $this->assertEquals(array(), self::$vfs->listFolder('test/dir2'));
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *   file1: content1
     */
    protected function _quota()
    {
        $used = 18 + filesize(__FILE__);
        self::$vfs->setQuota(18 + filesize(__FILE__) + 10);
        try {
            self::$vfs->writeData('', 'file1', '12345678901');
            $this->fail('Writing over quota should throw an exception');
        } catch (Horde_Vfs_Exception $e) {
        }
        self::$vfs->writeData('', 'file1', '1234567890');
        $this->assertTrue(self::$vfs->exists('', 'file1'));
        $this->assertEquals(array('limit' => $used + 10, 'usage' => $used + 10),
                            self::$vfs->getQuota());
        try {
            self::$vfs->writeData('', 'file2', '1');
            $this->fail('Writing over quota should throw an exception');
        } catch (Horde_Vfs_Exception $e) {
        }
        self::$vfs->deleteFile('', 'file1');
        $this->assertFalse(self::$vfs->exists('', 'file1'));
        $this->assertEquals(array('limit' => $used + 10, 'usage' => $used),
                            self::$vfs->getQuota());
        self::$vfs->writeData('', 'file2', '1');
        self::$vfs->setQuota(-1);
        self::$vfs->deleteFile('', 'file2');
    }

    /**
     * Structure after test:
     * test/
     *   dir1/
     *     file1: content1_1
     *     file2: __FILE__
     *   dir2/
     *   .file2: content2
     *   file1: content1
     * file2: 1
     */
    protected function _listFolder()
    {
        try {
            self::$vfs->listFolder('nonexistant_foobar');
            $this->fail('Listing non-existant folders should throw an exception');
        } catch (Horde_Vfs_Exception $e) {
        }
        self::$vfs->writeData('', 'file2', '1');
        $this->assertEquals(
            array('file2', 'test'),
            array_keys($this->_sort(self::$vfs->listFolder('/'))));
        $this->assertEquals(
            array('file2' => null, 'test' => array()),
            $this->_sort(self::$vfs->listFolder('')));
        $this->assertEquals(
            array('test' => array()),
            $this->_sort(self::$vfs->listFolder('', null, true, true)));
        self::$vfs->writeData('test', '.file2', 'content2');
        $this->assertEquals(
            array('file2' => null,
                  'test' => array('.file2' => null,
                                  'dir1' => array('file1' => null,
                                                  'file2' => null),
                                  'dir2' => array(),
                                  'file1' => null)),
            $this->_sort(self::$vfs->listFolder('', null, true, false, true)));
        $this->assertEquals(
            array('dir1' => array('file1' => null,
                                  'file2' => null),
                  'dir2' => array(),
                  'file1' => null),
            $this->_sort(self::$vfs->listFolder('test', null, false, false, true)));
        $this->assertEquals(
            array('.file2' => null,
                  'dir2' => array()),
            $this->_sort(self::$vfs->listFolder('test', '^.*1$')));
    }

    protected function _sort($folders)
    {
        ksort($folders);
        foreach ($folders as &$item) {
            if ($item['type'] == '**dir') {
                if (!empty($item['subdirs'])) {
                    $item = $this->_sort($item['subdirs']);
                } else {
                    $item = array();
                }
            } else {
                $item = null;
            }
        }
        return $folders;
    }
}