<?php
/**
 * Test the handling of cached share data.
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
 * Test the handling of cached share data.
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
class Horde_Kolab_Storage_Unit_List_Query_Share_CacheTest
extends PHPUnit_Framework_TestCase
{
    public function testInitDescription()
    {
        $this->query = $this->getMock('Horde_Kolab_Storage_List_Query_Share');
        $this->cache = $this->getMock('Horde_Kolab_Storage_List_Cache', array(), array(), '', false, false);
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_Share_Cache::DESCRIPTIONS)
            ->will($this->returnValue(true));
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_Share_Cache::DESCRIPTIONS)
            ->will(
                $this->returnValue(
                    array('INBOX' => 'Description')
                )
            );
        $this->query->expects($this->never())
            ->method('getDescription');
        $share = new Horde_Kolab_Storage_List_Query_Share_Cache(
            $this->query, $this->cache
        );
        $this->assertEquals('Description', $share->getDescription('INBOX'));
    }

    public function testGetDescription()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getDescription')
            ->with('INBOX')
            ->will($this->returnValue('description'));
        $this->assertEquals('description', $share->getDescription('INBOX'));
    }

    public function testCachedGetDescription()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getDescription')
            ->with('INBOX')
            ->will($this->returnValue('description'));
        $share->getDescription('INBOX');
        $this->assertEquals('description', $share->getDescription('INBOX'));
    }

    public function testStoredGetDescription()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getDescription')
            ->with('INBOX')
            ->will($this->returnValue('description'));
        $this->cache->expects($this->once())
            ->method('setQuery')
            ->with(
                Horde_Kolab_Storage_List_Query_Share_Cache::DESCRIPTIONS,
                array('INBOX' => 'description')
            );
        $this->cache->expects($this->once())
            ->method('save');
        $share->getDescription('INBOX');
    }

    public function testInitParameter()
    {
        $this->query = $this->getMock('Horde_Kolab_Storage_List_Query_Share');
        $this->cache = $this->getMock('Horde_Kolab_Storage_List_Cache', array(), array(), '', false, false);
        $this->cache->expects($this->once())
            ->method('hasLongTerm')
            ->with(Horde_Kolab_Storage_List_Query_Share_Cache::PARAMETERS)
            ->will($this->returnValue(true));
        $this->cache->expects($this->once())
            ->method('getLongTerm')
            ->with(Horde_Kolab_Storage_List_Query_Share_Cache::PARAMETERS)
            ->will(
                $this->returnValue(
                    array('INBOX' => array('params'))
                )
            );
        $this->query->expects($this->never())
            ->method('getParameters');
        $share = new Horde_Kolab_Storage_List_Query_Share_Cache(
            $this->query, $this->cache
        );
        $this->assertEquals(array('params'), $share->getParameters('INBOX'));
    }

    public function testGetParameter()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getParameters')
            ->with('INBOX')
            ->will($this->returnValue(array('params')));
        $this->assertEquals(array('params'), $share->getParameters('INBOX'));
    }

    public function testCachedGetParameter()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getParameters')
            ->with('INBOX')
            ->will($this->returnValue(array('params')));
        $share->getParameters('INBOX');
        $this->assertEquals(array('params'), $share->getParameters('INBOX'));
    }

    public function testStoredGetParameter()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getParameters')
            ->with('INBOX')
            ->will($this->returnValue(array('params')));
        $this->cache->expects($this->once())
            ->method('setLongTerm')
            ->with(
                Horde_Kolab_Storage_List_Query_Share_Cache::PARAMETERS,
                array('INBOX' => array('params'))
            );
        $this->cache->expects($this->once())
            ->method('save');
        $share->getParameters('INBOX');
    }

    public function testSetDescription()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('setDescription')
            ->with('INBOX', 'Description');
        $share->setDescription('INBOX', 'Description');
    }

    public function testCacheSetDescription()
    {
        $share = $this->_getShare();
        $this->cache->expects($this->once())
            ->method('setQuery')
            ->with(
                Horde_Kolab_Storage_List_Query_Share_Cache::DESCRIPTIONS,
                array('INBOX' => 'Description')
            );
        $this->cache->expects($this->once())
            ->method('save');
        $share->setDescription('INBOX', 'Description');
    }

    public function testSetGetDescription()
    {
        $share = $this->_getShare();
        $share->setDescription('INBOX', 'TEST');
        $this->assertEquals('TEST', $share->getDescription('INBOX'));
    }

    public function testSetParameters()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('setParameters')
            ->with('INBOX', array('params'));
        $share->setParameters('INBOX', array('params'));
    }

    public function testCacheSetParameters()
    {
        $share = $this->_getShare();
        $this->cache->expects($this->once())
            ->method('setLongTerm')
            ->with(
                Horde_Kolab_Storage_List_Query_Share_Cache::PARAMETERS,
                array('INBOX' => array('params'))
            );
        $this->cache->expects($this->once())
            ->method('save');
        $share->setParameters('INBOX', array('params'));
    }

    public function testSetGetParameters()
    {
        $share = $this->_getShare();
        $share->setParameters('INBOX', array('params'));
        $this->assertEquals(array('params'), $share->getParameters('INBOX'));
    }

    public function testUpdateAfterCreateFolder()
    {
        $share = $this->_getShare();
        $this->query->expects($this->never())
            ->method('getDescription');
        $share->updateAfterCreateFolder('INBOX');
    }

    public function testUpdateDescriptionAfterDeleteFolder()
    {
        $share = $this->_getShare();
        $this->query->expects($this->exactly(2))
            ->method('getDescription')
            ->with('INBOX')
            ->will($this->returnValue('Description'));
        $share->getDescription('INBOX');
        $share->updateAfterDeleteFolder('INBOX');
        $this->assertEquals('Description', $share->getDescription('INBOX'));
    }

    public function testUpdateParametersAfterDeleteFolder()
    {
        $share = $this->_getShare();
        $this->query->expects($this->exactly(2))
            ->method('getParameters')
            ->with('INBOX')
            ->will($this->returnValue(array('params')));
        $share->getParameters('INBOX');
        $share->updateAfterDeleteFolder('INBOX');
        $this->assertEquals(array('params'), $share->getParameters('INBOX'));
    }

    public function testUpdateDescriptionAfterRenameFolder()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getDescription')
            ->with('FOO')
            ->will($this->returnValue('Description'));
        $share->getDescription('FOO');
        $share->updateAfterRenameFolder('FOO', 'BAR');
        $this->assertEquals('Description', $share->getDescription('BAR'));
    }

    public function testUpdateParametersAfterRenameFolder()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getParameters')
            ->with('FOO')
            ->will($this->returnValue(array('params')));
        $share->getParameters('FOO');
        $share->updateAfterRenameFolder('FOO', 'BAR');
        $this->assertEquals(array('params'), $share->getParameters('BAR'));
    }

    public function testSynchronizeDescription()
    {
        $share = $this->_getShare();
        $this->query->expects($this->exactly(2))
            ->method('getDescription')
            ->with('INBOX')
            ->will($this->returnValue('Description'));
        $share->getDescription('INBOX');
        $share->synchronize();
        $this->assertEquals('Description', $share->getDescription('INBOX'));
    }

    public function testSynchronizeParameters()
    {
        $share = $this->_getShare();
        $this->query->expects($this->once())
            ->method('getParameters')
            ->with('INBOX')
            ->will($this->returnValue(array('params')));
        $share->getParameters('INBOX');
        $share->synchronize();
        $this->assertEquals(array('params'), $share->getParameters('INBOX'));
    }

    private function _getShare()
    {
        $this->query = $this->getMock('Horde_Kolab_Storage_List_Query_Share');
        $this->cache = $this->getMock('Horde_Kolab_Storage_List_Cache', array(), array(), '', false, false);
        return new Horde_Kolab_Storage_List_Query_Share_Cache(
            $this->query, $this->cache
        );
    }
}