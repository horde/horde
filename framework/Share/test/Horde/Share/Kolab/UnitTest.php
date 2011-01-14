<?php
/**
 * Unit testing for the Kolab driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Share
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Unit testing for the Kolab driver.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Share
 */
class Horde_Share_Kolab_UnitTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!interface_exists('Horde_Kolab_Storage')) {
            $this->markTestSkipped('The Kolab_Storage package seems to be unavailable.');
        }
    }

    public function testGetStorage()
    {
        $storage = $this->getMock('Horde_Kolab_Storage');
        $driver = $this->_getDriver();
        $driver->setStorage($storage);
        $this->assertSame($storage, $driver->getStorage());
    }

    /**
     * @expectedException Horde_Share_Exception
     */
    public function testStorageMissing()
    {
        $driver = $this->_getDriver();
        $driver->getStorage();
    }

    public function testListArray()
    {
        $this->assertType(
            'array',
            $this->_getCompleteDriver()->listShares('john')
        );
    }

    private function _getCompleteDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $driver = $this->_getDriver();
        $driver->setStorage(
            $factory->createFromParams(
                array(
                    'driver' => 'mock',
                    'data'   => array('user/john' => array()),
                    'cache'  => new Horde_Cache(
                        new Horde_Cache_Storage_Mock()
                    ),
                )
            )
        );
        return $driver;
    }

    private function _getDriver()
    {
        return new Horde_Share_Kolab(
            'test', 'john', new Horde_Perms(), new Horde_Group_Test()
        );
    }
}