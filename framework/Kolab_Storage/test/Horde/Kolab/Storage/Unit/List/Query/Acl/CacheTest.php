<?php
/**
 * Test the cached handling of ACL.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the cached handling of ACL.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
            ->method('getNamespace')
            ->will($this->returnValue(new Horde_Kolab_Storage_Folder_Namespace_Fixed('test')));
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
            ->method('getNamespace')
            ->will($this->returnValue(new Horde_Kolab_Storage_Folder_Namespace_Fixed('test')));
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
            ->method('getNamespace')
            ->will($this->returnValue(new Horde_Kolab_Storage_Folder_Namespace_Fixed('test')));
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