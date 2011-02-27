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
        $list = $this->getMock('Horde_Kolab_Storage_List');
        $storage->expects($this->once())
            ->method('getList')
            ->will($this->returnValue($list));
        $driver = $this->_getDriver();
        $driver->setStorage($storage);
        $this->assertSame($list, $driver->getList());
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
        $this->assertInternalType(
            'array',
            $this->_getCompleteDriver()->listShares('john')
        );
    }

    public function testGetTypeString()
    {
        $driver = new Horde_Share_Kolab(
            'mnemo', 'john', new Horde_Perms(), new Horde_Group_Test()
        );        
        $this->assertInternalType('string', $driver->getType());
    }

    public function testMnemoSupport()
    {
        $driver = new Horde_Share_Kolab(
            'mnemo', 'john', new Horde_Perms(), new Horde_Group_Test()
        );        
        $this->assertEquals('note', $driver->getType());
    }

    public function testKronolithSupport()
    {
        $driver = new Horde_Share_Kolab(
            'kronolith', 'john', new Horde_Perms(), new Horde_Group_Test()
        );        
        $this->assertEquals('event', $driver->getType());
    }

    public function testTurbaSupport()
    {
        $driver = new Horde_Share_Kolab(
            'turba', 'john', new Horde_Perms(), new Horde_Group_Test()
        );        
        $this->assertEquals('contact', $driver->getType());
    }

    public function testNagSupport()
    {
        $driver = new Horde_Share_Kolab(
            'nag', 'john', new Horde_Perms(), new Horde_Group_Test()
        );        
        $this->assertEquals('task', $driver->getType());
    }

    /**
     * @expectedException Horde_Share_Exception
     */
    public function testSupportException()
    {
        $driver = new Horde_Share_Kolab(
            'NOTSUPPORTED', 'john', new Horde_Perms(), new Horde_Group_Test()
        );        
    }

    public function testListIds()
    {
        $this->assertEquals(
            array('internal_id'),
            array_keys(
                $this->_getPrefilledDriver()->listShares('john')
            )
        );
    }

    /**
     * @expectedException Horde_Share_Exception
     */
    public function testUndefinedId()
    {
        $object = new Horde_Share_Object_Kolab(null, new Horde_Group_Mock());
        $object->getId();
    }

    /**
     * @expectedException Horde_Share_Exception
     */
    public function testUndefinedName()
    {
        $object = new Horde_Share_Object_Kolab(null, new Horde_Group_Mock());
        $object->getName();
    }

    public function testUndefinedPermissionId()
    {
        $object = new Horde_Share_Object_Kolab(null, new Horde_Group_Mock());
        $this->assertType(
            'string',
            $object->getPermissionId()
        );
    }

    public function testIdFromName()
    {
        $share = $this->_getCompleteDriver();
        $object = $share->newShare('john', 'IGNORED', 'test');
        $this->assertEquals(
            array('john', 'test'), $this->_decodeId($object->getId())
        );
    }

    public function testObjectId()
    {
        $object = new Horde_Share_Object_Kolab('test', new Horde_Group_Mock());
        $this->assertEquals('test', $object->getId());
    }

    public function testObjectName()
    {
        $object = new Horde_Share_Object_Kolab('test', new Horde_Group_Mock());
        $this->assertEquals('test', $object->getName());
    }

    public function testGetShare()
    {
        $this->assertEquals(
            array('john', 'Calendar'),
            $this->_decodeId(
                $this->_getPrefilledDriver()->getShare('internal_id')->getId()
            )
        );
    }

    public function testGetShareName()
    {
        $this->assertEquals(
            'internal_id',
            $this->_getPrefilledDriver()->getShare('internal_id')->getName()
        );
    }

    public function testExistsById()
    {
        $this->assertTrue(
            $this->_getPrefilledDriver()->exists($this->_getId('john', 'Calendar'))
        );
    }

    public function testExistsByName()
    {
        $this->assertTrue(
            $this->_getPrefilledDriver()->exists('internal_id')
        );
    }

    public function testDoesNotExists()
    {
        $this->assertFalse(
            $this->_getPrefilledDriver()->exists($this->_getId('john', 'DOES_NOT_EXIST'))
        );
    }

    public function testIdExists()
    {
        $this->assertTrue(
            $this->_getPrefilledDriver()->idExists($this->_getId('john', 'Calendar'))
        );
    }

    public function testIdDoesNotExists()
    {
        $this->assertFalse(
            $this->_getPrefilledDriver()->idExists($this->_getId('john', 'DOES_NOT_EXIST'))
        );
    }

    public function testGetShareById()
    {
        $this->assertEquals(
            array('john', 'Calendar'),
            $this->_decodeId(
                $this->_getPrefilledDriver()
                ->getShareById($this->_getId('john', 'Calendar'))
                ->getId()
            )
        );
    }

    /**
     * @expectedException Horde_Exception_NotFound
     */
    public function testMissingShare()
    {
        $this->_getPrefilledDriver()->getShareById('DOES_NOT_EXIST');
    }

    public function testShareOwner()
    {
        $this->assertEquals(
            'john',
            $this->_getPrefilledDriver()
            ->getShareById($this->_getId('john', 'Calendar'))
            ->get('owner')
        );
    }

    public function testShareName()
    {
        $this->assertEquals(
            'Calendar',
            $this->_getPrefilledDriver()
            ->getShareById($this->_getId('john', 'Calendar'))
            ->get('name')
        );
    }

    public function testShareDescription()
    {
        $this->assertEquals(
            'DESCRIPTION',
            $this->_getPrefilledDriver()
            ->getShareById($this->_getId('john', 'Calendar'))
            ->get('desc')
        );
    }

    public function testShareShareName()
    {
        $this->assertEquals(
            'internal_id',
            $this->_getPrefilledDriver()
            ->getShareById($this->_getId('john', 'Calendar'))
            ->get('share_name')
        );
    }

    public function testShareFolder()
    {
        $this->assertEquals(
            'INBOX/Calendar',
            $this->_getPrefilledDriver()
            ->getShareById($this->_getId('john', 'Calendar'))
            ->get('folder')
        );
    }

    public function testNewShare()
    {
        $this->assertEquals(
            'john',
            $this->_getPrefilledDriver()
            ->newShare('john', 'IGNORE', 'test')
            ->get('owner')
        );
    }

    public function testAddShare()
    {
        $share = $this->_getPrefilledDriver();
        $object = $share->newShare('john', 'IGNORED', 'Test');
        $share->addShare($object);
        $this->assertEquals(
            array('john', 'Test'),
            $this->_decodeId(
                $share->getShareById($this->_getId('john', 'Test'))->getId()
            )
        );
    }

    public function testShareAddedToList()
    {
        $share = $this->_getPrefilledDriver();
        $object = $share->newShare('john', 'SHARE_NAME', 'Test');
        $share->addShare($object);
        $this->assertContains(
            'SHARE_NAME',
            array_keys($share->listShares('john'))
        );
    }

    public function testDeleteShare()
    {
        $share = $this->_getPrefilledDriver();
        $object = $share->newShare('john', 'NAME', 'Test');
        $share->addShare($object);
        $share->removeShare($object);
        $this->assertNotContains(
            'NAME',
            array_keys($share->listShares('john'))
        );
    }

    public function testGetPermission()
    {
        $share = $this->_getPrefilledDriver();
        $object = $share->newShare('john', 'IGNORED', 'Test');
        $share->addShare($object);
        $this->assertEquals(
            30,
            $share->getShareById($this->_getId('john', 'Test'))->getPermission()->getCreatorPermissions()
        );
    }

    public function testSetPermission()
    {
        $share = $this->_getPrefilledDriver();
        $object = $share->newShare('john', 'IGNORED', 'Test');
        $object->addUserPermission('tina', Horde_Perms::SHOW);
        $share->addShare($object);
        $this->assertTrue(
            $share->getShareById($this->_getId('john', 'Test'))
            ->hasPermission('tina', Horde_Perms::SHOW)
        );
    }

    public function testNewShareName()
    {
        $share = $this->_getCompleteDriver();
        $object = $share->newShare('john', 'NAME', 'test');
        $this->assertEquals('NAME', $object->get('share_name'));
    }

    public function testSetDescription()
    {
        $share = $this->_getPrefilledDriver()
            ->getShareById($this->_getId('john', 'Calendar'));
        $share->set('desc', 'NEW');
        $share->save();
        $query = 
        $this->assertEquals(
            'NEW',
            $this->list
            ->getQuery(Horde_Kolab_Storage_List::QUERY_SHARE)
            ->getDescription('INBOX/Calendar')
        );
    }

    public function testSetData()
    {
        $share = $this->_getPrefilledDriver()
            ->getShareById($this->_getId('john', 'Calendar'));
        $share->set('other', 'OTHER');
        $share->save();
        $this->assertEquals(
            array(
                'other' => 'OTHER',
                'share_name' => 'internal_id'
            ),
            $this->list
            ->getQuery(Horde_Kolab_Storage_List::QUERY_SHARE)
            ->getParameters('INBOX/Calendar')
        );
    }

    public function testListShareCache()
    {
        $storage = $this->getMock('Horde_Kolab_Storage');
        $list = $this->getMock('Horde_Kolab_Storage_List');
        $query = $this->getMock('Horde_Kolab_Storage_List_Query_List');
        $query->expects($this->once())
            ->method('listByType')
            ->will($this->returnValue(array()));
        $list->expects($this->exactly(3))
            ->method('getQuery')
            ->will($this->returnValue($query));
        $storage->expects($this->exactly(3))
            ->method('getList')
            ->will($this->returnValue($list));
        $driver = $this->_getDriver();
        $driver->setStorage($storage);
        $driver->listShares('test');
        $driver->listShares('test');
    }

    public function testEditableShares()
    {
        $this->assertEquals(
            1,
            count(
                $this->_getPermissionDriver()
                ->listShares('john', array('perm' => Horde_Perms::EDIT))
            )
        );
    }

    public function testRootLevel()
    {
        $this->assertEquals(
            3,
            count(
                $this->_getHierarchyDriver()
                ->listShares('john', array('all_levels' => false))
            )
        );
    }

    private function _getPrefilledDriver()
    {
        return $this->_getDriverWithData($this->_getPrefilledData());
    }

    private function _getCompleteDriver()
    {
        return $this->_getDriverWithData($this->_getCompleteData());
    }

    private function _getPermissionDriver()
    {
        return $this->_getDriverWithData($this->_getPermissionData());
    }

    private function _getHierarchyDriver()
    {
        return $this->_getDriverWithData($this->_getHierarchyData());
    }

    private function _getDriverWithData($data)
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'queryset' => array('list' => array('queryset' => 'horde')),
                'params' => $data,
                'cache'  => new Horde_Cache(
                    new Horde_Cache_Storage_Mock()
                ),
            )
        );
        $driver = $this->_getDriver('kronolith');
        $this->storage = $factory->create();
        $this->list = $this->storage->getList();
        $this->list->synchronize();
        $driver->setStorage($this->storage);
        return $driver;
    }

    private function _getPrefilledData()
    {
        return array(
            'username' => 'john',
            'data'   => $this->_getMockData(
                array(
                    'user/john' => array(),
                    'user/john/Calendar' => array(
                        'a' => array(
                            '/shared/vendor/kolab/folder-type' => 'event.default',
                            '/shared/comment' => 'DESCRIPTION',
                            '/shared/vendor/horde/share-params' => base64_encode(serialize(array('share_name' => 'internal_id'))),
                        ),
                    ),
                )
            ),
        );
    }

    private function _getCompleteData()
    {
        return array(
            'username' => 'john',
            'data'   => $this->_getMockData(
                array(
                    'user/john' => null,
                )
            ),
        );
    }

    private function _getPermissionData()
    {
        return array(
            'username' => 'john',
            'data'   => $this->_getMockData(
                array(
                    'user/john' => array(),
                    'user/john/Calendar' => array(
                        't' => 'event.default',
                        'p' => array('john' => 'alrid'),
                    ),
                    'user/john/Listable' => array(
                        't' => 'event',
                        'p' => array('john' => 'l'),
                    ),
                )
            ),
        );
    }

    private function _getHierarchyData()
    {
        return array(
            'username' => 'john',
            'data'   => $this->_getMockData(
                array(
                    'user/john' => array(),
                    'user/john/Calendar' => array('t' => 'event.default'),
                    'user/john/Calendar/Private' => null,
                    'user/john/Calendar/Private/Family' => array('t' => 'event'),
                    'user/john/Calendar/Private/Family/Cooking' => array('t' => 'event'),
                    'user/john/Calendar/Private/Family/Party' => array('t' => 'event'),
                    'user/john/Work' => array('t' => 'event'),
                )
            ),
        );
    }

    private function _getDriver($app = 'mnemo')
    {
        return new Horde_Share_Kolab(
            $app, 'john', new Horde_Perms(), new Horde_Group_Test()
        );
    }

    private function _getMockData($elements)
    {
        $result = array();
        foreach ($elements as $path => $element) {
            if (!isset($element['p'])) {
                $folder = array('permissions' => array('anyone' => 'alrid'));
            } else {
                $folder = array('permissions' => $element['p']);
            }
            if (isset($element['a'])) {
                $folder['annotations'] = $element['a'];
            }
            if (isset($element['t'])) {
                $folder['annotations'] = array(
                    '/shared/vendor/kolab/folder-type' => $element['t'],
                );
            }
            $result[$path] = $folder;
        }
        return $result;
    }

    private function _getId($owner, $name)
    {
        return base64_encode(serialize(array($owner, $name)));
    }

    private function _decodeId($id)
    {
        return unserialize(base64_decode($id));
    }
}