<?php
/**
 * Test the cached handling of ACL.
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
 * Test the cached handling of ACL.
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
class Horde_Kolab_Storage_Unit_List_Query_Acl_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testHasAclSupport()
    {
        $this->assertTrue($this->_getAcl()->hasAclSupport());
    }

    public function testCachedAclSupport()
    {
        $acl = $this->_getAcl();
        $acl->hasAclSupport();
        $this->assertTrue($acl->hasAclSupport());
    }

    public function testGetAcl()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->once())
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue('lra'));
        $this->driver->expects($this->once())
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->assertEquals(array('user' => 'lra'), $acl->getAcl('INBOX'));
    }

    public function testCachedGetAcl()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->once())
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue('lra'));
        $this->driver->expects($this->once())
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAcl('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getAcl('INBOX'));
    }

    public function testPurging()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->exactly(2))
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue('lra'));
        $this->driver->expects($this->exactly(2))
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAcl('INBOX');
        $acl->deleteAcl('INBOX', 'user');
        $this->assertEquals(array('user' => 'lra'), $acl->getAcl('INBOX'));
    }

    public function testGetMyAcl()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->once())
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue('lra'));
        $this->assertEquals('lra', $acl->getMyAcl('INBOX'));
    }

    public function testCachedGetMyAcl()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->once())
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue('lra'));
        $acl->getMyAcl('INBOX');
        $this->assertEquals('lra', $acl->getMyAcl('INBOX'));
    }

    public function testPurgMyAcl()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->exactly(2))
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue('lra'));
        $acl->getMyAcl('INBOX');
        $acl->deleteAcl('INBOX', 'user');
        $this->assertEquals('lra', $acl->getMyAcl('INBOX'));
    }

    public function testGetAllAcl()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->once())
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testCachedGetAllAcl()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->once())
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAllAcl('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testPurgAllAcl()
    {
        $acl = $this->_getAcl();
        $this->driver->expects($this->exactly(2))
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAllAcl('INBOX');
        $acl->deleteAcl('INBOX', 'user');
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    private function _getAcl($has_support = true)
    {
        $this->driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $this->list = new Horde_Kolab_Storage_List_Base(
            $this->driver,
            new Horde_Kolab_Storage_Factory()
        );
        $this->driver->expects($this->once())
            ->method('hasAclSupport')
            ->will($this->returnValue($has_support));
        return new Horde_Kolab_Storage_List_Query_Acl_Cache(
            $this->list,
            array(
                'cache' => $this->getMockListCache()
            )
        );
    }
}