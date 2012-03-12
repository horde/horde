<?php
/**
 * Tests the Autoloader cache.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Autoloader_Cache
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Autoloader_Cache
 */

require_once dirname(__FILE__) . '/Stub/TestCache.php';

/**
 * Tests the Autoloader cache.
 *
 * @category   Horde
 * @package    Autoloader_Cache
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Autoloader_Cache
 */
class Horde_Autoloader_CacheTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (extension_loaded('xcache')) {
            $this->markTestSkipped('Xcache is active and it does not support the command line.');
        }
        $this->autoloader = $this->getMock('Horde_Autoloader');
        $this->cache = new Horde_Autoloader_Cache_Stub_TestCache(
            $this->autoloader
        );
    }

    public function tearDown()
    {
        $this->cache->prune();
    }

    public function testTypeApc()
    {
        if (!extension_loaded('apc')) {
            $this->markTestSkipped('APC not active.');
        }
        $this->assertEquals(
            Horde_Autoloader_Cache::APC,
            $this->cache->getType()
        );
    }

    public function testTypeEaccelerator()
    {
        if (!extension_loaded('eaccelerator')) {
            $this->markTestSkipped('Eaccelerator not active.');
        }
        $this->assertEquals(
            Horde_Autoloader_Cache::EACCELERATOR,
            $this->cache->getType()
        );
    }

    public function testTypeTempfile()
    {
        if (extension_loaded('eaccelerator')
            || extension_loaded('apc')) {
            $this->markTestSkipped('Caching engine active.');
        }
        $this->assertEquals(
            Horde_Autoloader_Cache::TEMPFILE,
            $this->cache->getType()
        );
    }

    public function testRegistering()
    {
        $this->cache->registerAutoloader();
        $this->assertContains(
            array($this->cache, 'loadClass'), spl_autoload_functions()
        );
        spl_autoload_unregister(array($this->cache, 'loadClass'));
    }

    public function testMapping()
    {
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->assertEquals('TEST', $this->cache->mapToPath('test'));
    }

    public function testSecondMapping()
    {
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->mapToPath('test');
    }

    public function testPathLoading()
    {
        $this->autoloader->expects($this->once())
            ->method('loadPath')
            ->with('TEST', 'test')
            ->will($this->returnValue(true));
        $this->assertTrue($this->cache->loadPath('TEST', 'test'));
    }

    public function testClassLoading()
    {
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->autoloader->expects($this->once())
            ->method('loadPath')
            ->with('TEST', 'test')
            ->will($this->returnValue(true));
        $this->assertTrue($this->cache->loadClass('test'));
    }

    public function testSecondClassLoading()
    {
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->autoloader->expects($this->exactly(2))
            ->method('loadPath')
            ->with('TEST', 'test')
            ->will($this->returnValue(true));
        $this->cache->loadClass('test');
        $this->cache->loadClass('test');
    }

    public function testSecondMappingWithSecondCache()
    {
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->store();
        $this->cache = new Horde_Autoloader_Cache_Stub_TestCache(
            $this->autoloader
        );
        $this->cache->mapToPath('test');
    }

    public function testSecondMappingWithPrunedCache()
    {
        $this->autoloader->expects($this->exactly(2))
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->store();
        $this->cache->prune();
        $this->cache = new Horde_Autoloader_Cache_Stub_TestCache(
            $this->autoloader
        );
        $this->cache->mapToPath('test');
    }

    public function testApcStore()
    {
        if (!extension_loaded('apc')) {
            $this->markTestSkipped('APC not active.');
        }
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->store();
        $this->assertEquals(
            array('test' => 'TEST'),
            apc_fetch($this->cache->getKey())
        );
    }

    public function testApcPrune()
    {
        if (!extension_loaded('apc')) {
            $this->markTestSkipped('APC not active.');
        }
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->store();
        $this->cache->prune();
        $this->assertEquals(
            array(),
            apc_fetch($this->cache->getKey())
        );
    }

    public function testFileStore()
    {
        if (extension_loaded('eaccelerator')
            || extension_loaded('apc')) {
            $this->markTestSkipped('Caching engine active.');
        }
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->store();
        $this->assertEquals(
            array('test' => 'TEST'),
            json_decode(file_get_contents(sys_get_temp_dir() . '/' . $this->cache->getKey()), true)
        );
    }

    public function testFilePrune()
    {
        if (extension_loaded('eaccelerator')
            || extension_loaded('apc')) {
            $this->markTestSkipped('Caching engine active.');
        }
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->store();
        $this->cache->prune();
        $this->assertFalse(
            file_exists(sys_get_temp_dir() . '/' . $this->cache->getKey())
        );
    }

}
