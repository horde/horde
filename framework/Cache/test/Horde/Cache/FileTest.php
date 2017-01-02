<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */

/**
 * This class tests the file backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_FileTest extends Horde_Cache_TestBase
{
    protected function _getCache($params = array())
    {
        $this->dir = sys_get_temp_dir() . '/horde_cache_test';
        if (!is_dir($this->dir)) {
            mkdir($this->dir);
        }
        return new Horde_Cache(
            new Horde_Cache_Storage_File(array_merge(
                array(
                    'dir'    => $this->dir,
                    'no_gc'  => true,
                    'prefix' => 'horde_cache_test'
                ),
                $params
            ))
        );
    }

    public function testSubdirectories()
    {
        $this->cache = $this->_getCache(array('sub' => 2));
        if (!$this->cache) {
            $this->markTestSkipped($this->reason);
        }
        $this->assertNull($this->cache->set('key1', 'data1', 0));
        $this->assertNull($this->cache->set('key2', 'data2', 0));
        $this->assertEquals(
            array($this->dir . '/7/', $this->dir . '/c/'),
            glob($this->dir . '/*', GLOB_MARK)
        );
        $this->assertEquals(
            array($this->dir . '/7/8/'),
            glob($this->dir . '/7/*', GLOB_MARK)
        );
        $this->assertEquals(
            array($this->dir . '/c/2/'),
            glob($this->dir . '/c/*', GLOB_MARK)
        );
        $this->assertEquals(
            array($this->dir . '/7/8/horde_cache_test78f825aaa0103319aaa1a30bf4fe3ada'),
            glob($this->dir . '/7/8/*', GLOB_MARK)
        );
        $this->assertEquals(
            array($this->dir . '/c/2/horde_cache_testc2add694bf942dc77b376592d9c862cd'),
            glob($this->dir . '/c/2/*', GLOB_MARK)
        );
    }

    public function testMigrateGarbageCollection()
    {
        mkdir($this->dir . '/c');
        mkdir($this->dir . '/7');
        mkdir($this->dir . '/3');
        $fp = fopen($this->dir . '/horde_cache_gc', 'w');
        $time = time();
        fwrite(
            $fp,
            $this->dir . "/c/c2add694bf942dc77b376592d9c862cd\t"
            . ($time - 100) . "\n"
        );
        fwrite(
            $fp,
            $this->dir . "/7/78f825aaa0103319aaa1a30bf4fe3ada \t"
            . ($time + 100) . "\n"
        );
        fwrite(
            $fp,
            $this->dir . "/3/3631578538a2d6ba5879b31a9a42f290\t"
            . ($time - 100) . "\n"
        );
        fwrite(
            $fp,
            $this->dir . "/3/3631578538a2d6ba5879b31a9a42f290\t"
            . ($time + 100) . "\n"
        );
        fwrite(
            $fp,
            $this->dir . "/c/caf8e34be07426ae7127c1b4829983c1\t"
            . ($time + 100) . "\n"
        );
        fwrite(
            $fp,
            $this->dir . "/c/caf8e34be07426ae7127c1b4829983c1\t"
            . ($time - 100) . "\n"
        );
        fclose($fp);

        $storage = new Horde_Cache_Stub_File(array(
            'dir'    => $this->dir,
            'no_gc'  => true,
            'prefix' => 'horde_cache_test',
            'sub'    => 1
        ));
        $this->cache = new Horde_Cache($storage);
        $storage->gc();

        $this->assertFileExists($this->dir . '/7/horde_cache_gc');
        $this->assertStringEqualsFile(
            $this->dir . '/7/horde_cache_gc',
            $this->dir . "/7/78f825aaa0103319aaa1a30bf4fe3ada \t"
            . ($time + 100) . "\n"
        );
        $this->assertFileExists($this->dir . '/3/horde_cache_gc');
        $this->assertStringEqualsFile(
            $this->dir . '/3/horde_cache_gc',
            $this->dir . "/3/3631578538a2d6ba5879b31a9a42f290\t"
            . ($time + 100) . "\n"
        );
        $this->assertFileExists($this->dir . '/c/horde_cache_gc');
        $this->assertStringEqualsFile(
            $this->dir . '/c/horde_cache_gc',
            $this->dir . "/c/c2add694bf942dc77b376592d9c862cd\t"
            . ($time - 100) . "\n"
            . $this->dir . "/c/caf8e34be07426ae7127c1b4829983c1\t"
            . ($time - 100) . "\n"
        );
        $this->assertFileNotExists($this->dir . '/horde_cache_gc');
    }

    public function testGarbageCollection()
    {
        $storage = new Horde_Cache_Stub_File(array(
            'dir'    => $this->dir,
            'no_gc'  => true,
            'prefix' => 'horde_cache_test',
            'sub'    => 2,
        ));
        $this->cache = new Horde_Cache($storage);
        $this->cache->set('key1', 'data1', -100);
        $this->cache->set('key2', 'data2', 100);
        $this->cache->set('key3', 'data3', -100);
        $this->cache->set('key3', 'data4', 100);
        $this->cache->set('key4', 'data5', 100);
        $this->cache->set('key4', 'data6', -100);
        $storage->gc();
        $this->assertFileExists($this->dir . '/7/8/horde_cache_test78f825aaa0103319aaa1a30bf4fe3ada');
        $this->assertFileExists($this->dir . '/3/6/horde_cache_test3631578538a2d6ba5879b31a9a42f290');
        $this->assertFileNotExists($this->dir . '/c/2/horde_cache_testc2add694bf942dc77b376592d9c862cd');
        $this->assertFileNotExists($this->dir . '/c/a/horde_cache_testcaf8e34be07426ae7127c1b4829983c1');
    }

    public function tearDown()
    {
        parent::tearDown();
        system('rm -r ' . $this->dir);
    }
}
