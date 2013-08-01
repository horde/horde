<?php
/**
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package    Autoloader_Cache
 * @subpackage UnitTests
 */

/**
 * Tests the Autoloader cache.
 *
 * NOTE: If you activate APC < 3.1.7 the tests wont run
 * (https://bugs.php.net/bug.php?id=58832)
 *
 * @author     Gunnar Wrobel <wrobel@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package    Autoloader_Cache
 * @subpackage UnitTests
 */
class Horde_Autoloader_CacheTest extends PHPUnit_Framework_TestCase
{
    private $autoloader;
    private $cache;

    public function setUp()
    {
        $this->autoloader = $this->getMock(
            'Horde_Autoloader',
            array(
                'loadClass',
                'registerAutoloader',
                'loadPath',
                'mapToPath',
                'someOther'
            )
        );
    }

    public function tearDown()
    {
        if ($this->cache) {
            $this->cache->prune();
        }
    }

    private function _loadCache()
    {
        $this->cache = new Horde_Autoloader_Cache_Stub_TestCache(
            $this->autoloader
        );
    }

    public function testTypeApc()
    {
        if (!extension_loaded('apc')) {
            $this->markTestSkipped('APC not active.');
        }
        $this->_loadCache();
        $this->assertEquals(
            'Horde_Autoloader_Cache_Backend_Apc',
            get_class($this->cache->getBackend())
        );
    }

    public function testTypeEaccelerator()
    {
        if (!extension_loaded('eaccelerator')) {
            $this->markTestSkipped('Eaccelerator not active.');
        }
        $this->_loadCache();
        $this->assertEquals(
            'Horde_Autoloader_Cache_Backend_Eaccelerator',
            get_class($this->cache->getBackend())
        );
    }

    public function testTypeTempfile()
    {
        if (extension_loaded('eaccelerator') || extension_loaded('apc')) {
            $this->markTestSkipped('Caching engine active.');
        }
        $this->_loadCache();
        $this->assertEquals(
            'Horde_Autoloader_Cache_Backend_Tempfile',
            get_class($this->cache->getBackend())
        );
    }

    public function testRegistering()
    {
        $this->_loadCache();
        $this->cache->registerAutoloader();
        $this->assertContains(
            array($this->cache, 'loadClass'), spl_autoload_functions()
        );
        spl_autoload_unregister(array($this->cache, 'loadClass'));
    }

    public function testMapping()
    {
        $this->_loadCache();
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->assertEquals('TEST', $this->cache->mapToPath('test'));
    }

    public function testSecondMapping()
    {
        $this->_loadCache();
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->mapToPath('test');
    }

    public function testPathLoading()
    {
        $this->_loadCache();
        $this->autoloader->expects($this->once())
            ->method('loadPath')
            ->with('TEST', 'test')
            ->will($this->returnValue(true));
        $this->assertTrue($this->cache->loadPath('TEST', 'test'));
    }

    public function testClassLoading()
    {
        $this->_loadCache();
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
        $this->_loadCache();
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
        $this->_loadCache();
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
        $this->_loadCache();
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

    public function testStore()
    {
        $this->_loadCache();
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->store();
        $this->assertEquals(
            array('test' => 'TEST'),
            $this->cache->getBackend()->fetch()
        );
    }

    public function testPrune()
    {
        $this->_loadCache();
        $this->autoloader->expects($this->once())
            ->method('mapToPath')
            ->with('test')
            ->will($this->returnValue('TEST'));
        $this->cache->mapToPath('test');
        $this->cache->store();
        $this->cache->prune();
        $this->assertEquals(
            array(),
            $this->cache->getBackend()->fetch()
        );
    }

    public function testArbitraryCalls()
    {
        $this->_loadCache();
        $this->autoloader->expects($this->once())
            ->method('someOther')
            ->with('A', 'B')
            ->will($this->returnValue(true));
        $this->assertTrue($this->cache->someOther('A', 'B'));
    }

}
