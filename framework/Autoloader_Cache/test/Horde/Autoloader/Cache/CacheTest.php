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
}
