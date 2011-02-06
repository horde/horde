<?php
/**
 * Integration test for the Kolab driver based on the in-memory mock driver.
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
require_once dirname(__FILE__) . '/../Base.php';

/**
 * Integration test for the Kolab driver based on the in-memory mock driver.
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
class Horde_Share_Kolab_MockTest extends Horde_Share_Test_Base
{
    protected static $storage;

    public static function setUpBeforeClass()
    {
        $group = new Horde_Group_Test();
        self::$share = new Horde_Share_Kolab('mnemo', 'john', new Horde_Perms(), $group);
        $factory = new Horde_Kolab_Storage_Factory();
        $storage = $factory->createFromParams(
            array(
                'driver' => 'mock',
                'params' => array(
                    'data'   => array('user/john' => array()),
                    'username' => 'john'
                ),
                'cache'  => new Horde_Cache(
                    new Horde_Cache_Storage_Mock()
                ),
            )
        );
        self::$storage = $storage->getList();
        $storage->addListQuery(self::$storage, Horde_Kolab_Storage_List::QUERY_SHARE);
        self::$storage->synchronize();
        self::$storage->getDriver()->setGroups(
            array(
                'john' => array('mygroup'),
            )
        );
        self::$share->setStorage(self::$storage);

        // FIXME
        $GLOBALS['injector'] = new Horde_Injector(new Horde_Injector_TopLevel());
        $GLOBALS['injector']->setInstance('Horde_Group', $group);
    }

    public function setUp()
    {
        if (!interface_exists('Horde_Kolab_Storage')) {
            $this->markTestSkipped('The Kolab_Storage package seems to be unavailable.');
        }
        self::$storage->getDriver()->setAuth('john');
        self::$storage->synchronize();
    }

    public function testGetApp()
    {
        $this->getApp('mnemo');
    }

    public function testAddShare()
    {
        $share = parent::addShare();
        $this->assertInstanceOf('Horde_Share_Object_Kolab', $share);
    }

    /**
     * @depends testAddShare
     */
    public function testPermissions()
    {
        self::$storage->getDriver()->setAuth('');
        $this->permissionsSystemShare();
        self::$storage->getDriver()->setAuth('john');
        $this->permissionsChildShare();
        self::$storage->getDriver()->setAuth('jane');
        $this->permissionsJaneShare();
        $this->permissionsGroupShare();
        $this->permissionsNoShare();

        self::$storage->getDriver()->setAuth('john');
        self::$storage->synchronize();
    }

    protected function permissionsNoShare()
    {
        // Foreign share without permissions.
        $fshare = self::$share->newShare('jane', 'noshare');
        //@todo: INTERFACE!!!
        $fshare->set('name', 'No Share');
        $fshare->save();
    }

    /**
     * @depends testAddShare
     */
    public function testExists()
    {
        $this->exists();
    }

    /**
     * @depends testPermissions
     */
    public function testCountShares()
    {
        $this->countShares();
    }

    /**
     * @depends testPermissions
     */
    public function testGetShare()
    {
        $share = $this->getShare();
        $this->assertInstanceOf('Horde_Share_Object_Kolab', $share);
    }

    /**
     * @depends testGetShare
     */
    public function testGetShareById()
    {
        $this->getShareById();
    }

    protected function _getShareById()
    {
        $myshare = self::$share->getShareById(self::$shares['myshare']->getId());
        $this->assertInstanceOf('Horde_Share_Object', $myshare);
        $this->assertEquals(self::$shares['myshare'], $myshare);
        $this->assertEquals('行事曆', $myshare->get('desc'));

        $janeshare = self::$share->getShareById(self::$shares['janeshare']->getId());
        //@todo: INTERFACE!!!
        $janeshare->getPermission();
        $this->assertInstanceOf('Horde_Share_Object', $janeshare);
        $this->assertEquals(self::$shares['janeshare'], $janeshare);
        $this->assertEquals(array('john', 'jane'), $janeshare->listUsers());
        $this->assertEquals(array('john', 'jane'), $janeshare->listUsers(Horde_Perms::EDIT));
        $this->assertEquals(array('jane'), $janeshare->listUsers(Horde_Perms::DELETE));
        $this->assertEquals('Jane\'s Share', $janeshare->get('name'));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::EDIT));
        //@todo: INTERFACE!!!
        //$this->assertTrue($janeshare->hasPermission('jane', 99999));

        $groupshare = self::$share->getShareById(self::$shares['groupshare']->getId());
        //@todo: INTERFACE!!!
        $groupshare->getPermission();
        $this->assertInstanceOf('Horde_Share_Object', $groupshare);
        $this->assertEquals(self::$shares['groupshare'], $groupshare);
        $this->assertEquals(array('mygroup'), $groupshare->listGroups());
        $this->assertEquals(array(), $groupshare->listGroups(Horde_Perms::EDIT));
        $this->assertEquals(array('mygroup'), $groupshare->listGroups(Horde_Perms::DELETE));
        $this->assertEquals('Group Share', $groupshare->get('name'));
    }


    /**
     * @depends testGetShare
     */
    public function testGetShares()
    {
        $this->getShares();
    }

    protected function _getShares()
    {
        $newshares = self::$share->getShares(array(self::$shares['myshare']->getId(), self::$shares['janeshare']->getId(), self::$shares['groupshare']->getId()));
        $this->assertEquals(
            array('myshare', 'janeshare', 'groupshare'),
            array_keys($newshares));
        $this->assertInstanceOf('Horde_Share_Object', $newshares['myshare']);
        $this->assertEquals(self::$shares['myshare'], $newshares['myshare']);
        //@todo: INTERFACE!!!
        $newshares['janeshare']->getPermission();
        $this->assertEquals(self::$shares['janeshare'], $newshares['janeshare']);
        //@todo: INTERFACE!!!
        $newshares['groupshare']->getPermission();
        $this->assertEquals(self::$shares['groupshare'], $newshares['groupshare']);
    }

    /**
     * @depends testPermissions
     */
    public function testListShares()
    {
        $this->_listSharesJohn();
        self::$storage->getDriver()->setAuth('');
        self::$storage->synchronize();
        $this->_listSharesSystem();
        self::$storage->getDriver()->setAuth('john');
        self::$storage->synchronize();
        self::$share->resetCache();
        $this->_listSharesJohnTwo();
    }

    public function _listSharesJohn()
    {
        // Default listing.
        $shares = self::$share->listShares('john');
        $this->assertInternalType('array', $shares);
        $this->assertEquals(5, count($shares));

        // Test arguments for default listing.
        $this->assertEquals($shares, self::$share->listShares('john', array('perm' => Horde_Perms::SHOW, 'attributes' => null, 'from' => 0, 'count' => 0, 'sort_by' => null, 'direction' => 0)));

        // Getting back the correct shares?
        $shares = self::$share->listShares('john', array('all_levels' => false, 'sort_by' => 'id'));
        //@todo: INTERFACE!!!
        $this->assertEquals(
            array('myshare', 'systemshare', 'groupshare', 'janeshare'),
            array_keys($shares));

        // Shares of a certain owner.
        $shares = self::$share->listShares('john', array('attributes' => 'jane', 'sort_by' => 'id'));
        //@todo: INTERFACE!!!
        $this->assertEquals(
            array('groupshare', 'janeshare'),
            array_keys($shares));
    }

    public function _listSharesSystem()
    {
        // Guest shares.
        //@todo: TYPO!!!
        //$share = self::$share->getShareById(2);
        $shares = self::$share->listShares(false, array('perm' => Horde_Perms::SHOW, 'sort_by' => 'id'));
        //@todo: INTERFACE!!!
        $this->assertEquals(
            array('systemshare', 'myshare'),
            array_keys($shares));
    }

    public function _listSharesJohnTwo()
    {
        // Shares with certain permissions.
        $this->assertEquals(5, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::EDIT, 'sort_by' => 'id'));
        $this->assertEquals(
            array('myshare', 'mychildshare', 'janeshare'),
            array_keys($shares));

        // Again with only toplevel
        $shares = self::$share->listShares('john', array('all_levels' => false, 'perm' => Horde_Perms::EDIT, 'sort_by' => 'id'));
        $this->assertEquals(
            array('myshare', 'janeshare'),
            array_keys($shares));

        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::DELETE, 'sort_by' => 'id'));
        $this->assertEquals(
            array('myshare', 'mychildshare', 'groupshare'),
            array_keys($shares));

        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::EDIT | Horde_Perms::DELETE, 'sort_by' => 'id'));
        //@todo: INTERFACE!!!
        $this->assertEquals(
            array('myshare', 'mychildshare', 'groupshare', 'janeshare'),
            array_keys($shares));
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL));
        $this->assertInternalType('array', $shares);
        $this->assertEquals(5, count($shares));

        // Paging.
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'from' => 2, 'count' => 2));
        //@todo: INTERFACE!!!
        $this->assertEquals(
            array('systemshare', 'groupshare'),
            array_keys($shares));

        // Paging with top level only
        $shares = self::$share->listShares('john', array('all_levels' => false, 'perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'from' => 2, 'count' => 2));
        //@todo: INTERFACE!!!
        $this->assertEquals(
            array('groupshare', 'janeshare'),
            array_keys($shares));

        // Restrict to children of a share only
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'parent' => self::$shares['myshare']));
        $this->assertEquals(
            array('mychildshare'),
            array_keys($shares));

        // Sort order and direction.
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'direction' => 1));
        //@todo: INTERFACE!!!
        $this->assertEquals(
            array('janeshare', 'groupshare', 'systemshare', 'mychildshare', 'myshare'),
            array_keys($shares));

        // Attribute searching.
        $shares = self::$share->listShares('john', array('attributes' => array('name' => 'Jane\'s Share')));
        $this->assertEquals(
            array('janeshare'),
            array_keys($shares));
        $shares = self::$share->listShares('john', array('attributes' => array('desc' => '行事曆')));
        $this->assertEquals(
            array('myshare'),
            array_keys($shares));
    }


    /**
     * @depends testPermissions
     */
    public function testGetPermission()
    {
        return $this->getPermission();
    }

    public function getPermission()
    {
        $permission = new Horde_Perms_Permission('myshare');
        $permission->addDefaultPermission(Horde_Perms::SHOW);
        //@todo: INTERFACE!!!
        //$permission->addGuestPermission(0);
        //@todo: INTERFACE!!!
        $permission->addCreatorPermission(30);
        $permission->addUserPermission('jane', Horde_Perms::SHOW);
        $permission->addGroupPermission('mygroup', Horde_Perms::SHOW);
        //@todo: INTERFACE!!!
        $this->assertEquals($permission->data, self::$shares['myshare']->getPermission()->data);
        self::$share->resetCache();
        //@todo: INTERFACE!!!
        $this->assertEquals($permission->data, self::$share->getShare('myshare')->getPermission()->data);
        self::$share->resetCache();
        $shares = self::$share->getShares(array(self::$shares['myshare']->getId()));
        //@todo: INTERFACE!!!
        $this->assertEquals($permission->data, $shares['myshare']->getPermission()->data);
        self::$share->resetCache();
        $shares = self::$share->listShares('john');
        //@todo: INTERFACE!!!
        $this->assertEquals($permission->data, $shares['myshare']->getPermission()->data);

        $permission = new Horde_Perms_Permission('systemshare');
        $permission->addDefaultPermission(Horde_Perms::SHOW | Horde_Perms::READ);
        $permission->addGuestPermission(Horde_Perms::SHOW);
        //@todo: INTERFACE!!!
        $permission->addCreatorPermission(30);
        //@todo: INTERFACE!!!
        $this->assertEquals($permission->data, self::$shares['systemshare']->getPermission()->data);

        $permission = new Horde_Perms_Permission('janeshare');
        //@todo: INTERFACE!!!
        //$permission->addDefaultPermission(0);
        //$permission->addGuestPermission(0);
        //@todo: INTERFACE!!!
        $permission->addCreatorPermission(30);
        $permission->addUserPermission('john', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT);
        //@todo: INTERFACE!!!
        $this->assertEquals($permission->data, self::$shares['janeshare']->getPermission()->data);

        $permission = new Horde_Perms_Permission('groupshare');
        //@todo: INTERFACE!!!
        //$permission->addDefaultPermission(0);
        //$permission->addGuestPermission(0);
        //@todo: INTERFACE!!!
        $permission->addCreatorPermission(30);
        $permission->addGroupPermission('mygroup', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::DELETE);
        //@todo: INTERFACE!!!
        $this->assertEquals($permission->data, self::$shares['groupshare']->getPermission()->data);
    }

    /**
     * @depends testPermissions
     */
    public function testRemoveUserPermissions()
    {
        self::$storage->getDriver()->setAuth('jane');
        $this->removeUserPermissionsJane();
        self::$storage->getDriver()->setAuth('john');
        self::$storage->synchronize();
        $this->removeUserPermissionsJohn();
    }

    /**
     * @depends testRemoveUserPermissions
     */
    public function testRemoveGroupPermissions()
    {
        $groupshare = self::$shares['groupshare'];
        self::$storage->getDriver()->setAuth('jane');
        $this->removeGroupPermissionsJane($groupshare);
        self::$storage->getDriver()->setAuth('john');
        self::$storage->synchronize();
        self::$share->resetCache();
        $this->removeGroupPermissionsJohn();
        self::$storage->getDriver()->setAuth('jane');
        $this->removeGroupPermissionsJaneTwo($groupshare);
        self::$storage->getDriver()->setAuth('john');
        self::$storage->synchronize();
        $this->removeGroupPermissionsJohnTwo();
    }

    /**
     * @depends testGetShare
     */
    public function testRemoveShare()
    {
        $this->removeShare();
    }

    public function removeShare()
    {
        // Getting shares from cache.
        $this->_removeShare();

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        //@todo: INTERFACE!!!
        //$this->_removeShare();
    }


    public function testCallback()
    {
        $this->callback(new Horde_Share_Object_Sql(array()));
    }
}

/**
 NOTES

 - listAllShares() does not really work as expected as we need manager access for that.
 - The share_id is different for each users
 - Permissions are always enforced.
 - Permissions are lazy loaded.
 - Kolab_Shares require a set('name')
 - listSystemShares not supported yet
 - The creator gets specific rights on a share
 - The returned permission representation is Horde_Perms_Permission_Kolab not Horde_Perms_Permission
 - Unset permissions won't be represented in the permission object.
 - Why can shares be removed twice?
 - Why wouldn't the system user see shares from other users?
 - The test assumes an the internal IDs in the sort order.
*/