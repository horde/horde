<?php
/**
 * Test the handling of cached active sync data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the handling of cached active sync data.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_List_Query_ActiveSync_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testGetActiveSync()
    {
        $activesync = $this->_getActivesync();
        $this->driver->expects($this->once())
            ->method('getAnnotation')
            ->with('INBOX', '/priv/vendor/kolab/activesync')
            ->will($this->returnValue('eyJ4IjoieSJ9'));
        $this->assertEquals(array('x' => 'y'), $activesync->getActiveSync('INBOX'));
    }

    public function testCachedGetActiveSync()
    {
        $activesync = $this->_getActivesync();
        $this->driver->expects($this->once())
            ->method('getAnnotation')
            ->with('INBOX', '/priv/vendor/kolab/activesync')
            ->will($this->returnValue('eyJ4IjoieSJ9'));
        $activesync->getActiveSync('INBOX');
        $this->assertEquals(array('x' => 'y'), $activesync->getActiveSync('INBOX'));
    }

    public function testSetActiveSync()
    {
        $activesync = $this->_getActivesync();
        $this->driver->expects($this->once())
            ->method('setAnnotation')
            ->with('INBOX', '/priv/vendor/kolab/activesync', 'eyJ4IjoieSJ9');
        $activesync->setActiveSync('INBOX', array('x' => 'y'));
    }

    public function testCachedSetParameters()
    {
        $activesync = $this->_getActivesync();
        $this->driver->expects($this->never())
            ->method('getAnnotation');
        $this->driver->expects($this->once())
            ->method('setAnnotation')
            ->with('INBOX', '/priv/vendor/kolab/activesync', 'eyJ4IjoieSJ9');
        $activesync->setActiveSync('INBOX', array('x' => 'y'));
        $this->assertEquals(array('x' => 'y'), $activesync->getActiveSync('INBOX'));
    }

    public function testDeleteFolder()
    {
        $activesync = $this->_getActivesync();
        $this->driver->expects($this->exactly(2))
            ->method('getAnnotation')
            ->with('INBOX', '/priv/vendor/kolab/activesync')
            ->will($this->returnValue('eyJ4IjoieSJ9'));
        $activesync->getActiveSync('INBOX');
        $activesync->deleteFolder('INBOX');
        $this->assertEquals(array('x' => 'y'), $activesync->getActiveSync('INBOX'));
    }

    public function testRenameFolder()
    {
        $activesync = $this->_getActivesync();
        $this->driver->expects($this->once())
            ->method('getAnnotation')
            ->with('INBOX', '/priv/vendor/kolab/activesync')
            ->will($this->returnValue('eyJ4IjoieSJ9'));
        $activesync->getActiveSync('INBOX');
        $activesync->renameFolder('INBOX', 'TEST');
        $this->assertEquals(array('x' => 'y'), $activesync->getActiveSync('TEST'));
    }

    private function _getActivesync()
    {
        $this->driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $this->list = new Horde_Kolab_Storage_List_Base(
            $this->driver,
            new Horde_Kolab_Storage_Factory()
        );
        return new Horde_Kolab_Storage_List_Query_ActiveSync_Cache(
            $this->list,
            array(
                'cache' => $this->getMockListCache()
            )
        );
    }
}