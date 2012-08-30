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
 * See the enclosed file COPYING for license information (LGPvL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_List_Query_Acl_CacheTest
extends PHPUnit_Framework_TestCase
{
    public function testInitGetAcl()
    {
        $this->query = $this->getMock('Horde_Kolab_Storage_List_Query_Acl');
        $this->cache = $this->getMock('Horde_Kolab_Storage_List_Cache', array(), array(), '', false, false);
        $this->cache->expects($this->exactly(3))
            ->method('hasQuery')
            ->with(
                $this->logicalOr(
                    Horde_Kolab_Storage_List_Query_Acl_Cache::ACL,
                    Horde_Kolab_Storage_List_Query_Acl_Cache::MYRIGHTS,
                    Horde_Kolab_Storage_List_Query_Acl_Cache::ALLRIGHTS
                )
            )->will($this->returnValue(true));
        $this->cache->expects($this->exactly(3))
            ->method('getQuery')
            ->with(
                $this->logicalOr(
                    Horde_Kolab_Storage_List_Query_Acl_Cache::ACL,
                    Horde_Kolab_Storage_List_Query_Acl_Cache::MYRIGHTS,
                    Horde_Kolab_Storage_List_Query_Acl_Cache::ALLRIGHTS
                )
            )->will($this->returnValue(array('INBOX' => array('user' => 'lra'))));
        $this->query->expects($this->never())
            ->method('getAcl');
        $this->query->expects($this->never())
            ->method('getMyAcl');
        $this->query->expects($this->never())
            ->method('getAllAcl');
        $acl = new Horde_Kolab_Storage_List_Query_Acl_Cache(
            $this->query, $this->cache
        );
        $this->assertEquals(array('user' => 'lra'), $acl->getAcl('INBOX'));
        $this->assertEquals(array('user' => 'lra'), $acl->getMyAcl('INBOX'));
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testHasAclSupport()
    {
        $acl = $this->_getAcl();
        $this->cache->expects($this->once())
            ->method('issetSupport')
            ->with(Horde_Kolab_Storage_List_Query_Acl_Cache::CAPABILITY)
            ->will($this->returnValue(true));
        $this->cache->expects($this->never())
            ->method('save');
        $this->cache->expects($this->once())
            ->method('hasSupport')
            ->with(Horde_Kolab_Storage_List_Query_Acl_Cache::CAPABILITY)
            ->will($this->returnValue(true));
        $this->assertTrue($acl->hasAclSupport());
    }

    public function testUncachedMissingAclSupport()
    {
        $acl = $this->_getAcl();
        $this->cache->expects($this->once())
            ->method('issetSupport')
            ->with(Horde_Kolab_Storage_List_Query_Acl_Cache::CAPABILITY)
            ->will($this->returnValue(false));
        $this->query->expects($this->once())
            ->method('hasAclSupport')
            ->will($this->returnValue(false));
        $this->cache->expects($this->once())
            ->method('setSupport')
            ->with(Horde_Kolab_Storage_List_Query_Acl_Cache::CAPABILITY, false)
            ->will($this->returnValue(false));
        $this->cache->expects($this->once())
            ->method('save');
        $this->cache->expects($this->once())
            ->method('hasSupport')
            ->with(Horde_Kolab_Storage_List_Query_Acl_Cache::CAPABILITY)
            ->will($this->returnValue(false));
        $this->assertFalse($acl->hasAclSupport());
    }

    public function testGetAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->assertEquals(array('user' => 'lra'), $acl->getAcl('INBOX'));
    }

    public function testCachedGetAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAcl('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getAcl('INBOX'));
    }

    public function testStoredGetAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->cache->expects($this->once())
            ->method('setQuery')
            ->with(Horde_Kolab_Storage_List_Query_Acl_Cache::ACL, array('INBOX' => array('user' => 'lra')));
        $this->cache->expects($this->once())
            ->method('save');
        $acl->getAcl('INBOX');
    }

    public function testPurgeGetAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->exactly(2))
            ->method('getAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAcl('INBOX');
        $acl->updateAfterDeleteFolder('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getAcl('INBOX'));
    }

    public function testGetMyAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->assertEquals(array('user' => 'lra'), $acl->getMyAcl('INBOX'));
    }

    public function testCachedGetMyAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getMyAcl('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getMyAcl('INBOX'));
    }

    public function testStoredGetMyAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->cache->expects($this->once())
            ->method('setQuery')
            ->with(Horde_Kolab_Storage_List_Query_Acl_Cache::MYRIGHTS, array('INBOX' => array('user' => 'lra')));
        $this->cache->expects($this->once())
            ->method('save');
        $acl->getMyAcl('INBOX');
    }


    public function testPurgeMyAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->exactly(2))
            ->method('getMyAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getMyAcl('INBOX');
        $acl->updateAfterDeleteFolder('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getMyAcl('INBOX'));
    }

    public function testGetAllAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testCachedGetAllAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAllAcl('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testStoredGetAllAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->cache->expects($this->once())
            ->method('setQuery')
            ->with(Horde_Kolab_Storage_List_Query_Acl_Cache::ALLRIGHTS, array('INBOX' => array('user' => 'lra')));
        $this->cache->expects($this->once())
            ->method('save');
        $acl->getAllAcl('INBOX');
    }

    public function testPurgeGetAllAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->exactly(2))
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAllAcl('INBOX');
        $acl->updateAfterDeleteFolder('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testDeleteAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('deleteAcl')
            ->with('INBOX', 'test');
        $acl->deleteAcl('INBOX', 'test');
    }

    public function testDeletePurgesAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->exactly(2))
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAllAcl('INBOX');
        $acl->deleteAcl('INBOX', 'test');
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testSetAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('setAcl')
            ->with('INBOX', 'test', 'lra');
        $acl->setAcl('INBOX', 'test', 'lra');
    }

    public function testSetPurgesAcl()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->exactly(2))
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAllAcl('INBOX');
        $acl->setAcl('INBOX', 'test', 'lra');
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testPurgeAfterRename()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->exactly(2))
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAllAcl('INBOX');
        $acl->updateAfterRenameFolder('INBOX', 'FOO');
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testCreateChangesNothing()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->once())
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAllAcl('INBOX');
        $acl->updateAfterCreateFolder('INBOX');
        $this->assertEquals(array('user' => 'lra'), $acl->getAllAcl('INBOX'));
    }

    public function testSynchronize()
    {
        $acl = $this->_getAcl();
        $this->query->expects($this->exactly(2))
            ->method('getAcl')
            ->with('FOO')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->query->expects($this->exactly(2))
            ->method('getMyAcl')
            ->with('BAR')
            ->will($this->returnValue(array('user' => 'lra')));
        $this->query->expects($this->exactly(2))
            ->method('getAllAcl')
            ->with('INBOX')
            ->will($this->returnValue(array('user' => 'lra')));
        $acl->getAcl('FOO');
        $acl->getMyAcl('BAR');
        $acl->getAllAcl('INBOX');
        $acl->synchronize();
        $acl->getAcl('FOO');
        $acl->getMyAcl('BAR');
        $acl->getAllAcl('INBOX');
    }

    private function _getAcl()
    {
        $this->query = $this->getMock('Horde_Kolab_Storage_List_Query_Acl');
        $this->cache = $this->getMock('Horde_Kolab_Storage_List_Cache', array(), array(), '', false, false);
        return new Horde_Kolab_Storage_List_Query_Acl_Cache(
            $this->query, $this->cache
        );
    }
}