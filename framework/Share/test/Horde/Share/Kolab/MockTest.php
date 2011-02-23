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
                    'data'   => array(
                        'user/' => array(
                            'permissions' => array('anyone' => 'alrid')
                        ),
                        'user/john' => array(
                            'permissions' => array('anyone' => 'alrid')
                        ),
                        'user/jane' => array(
                            'permissions' => array('anyone' => 'alrid')
                        ),
                    ),
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
        self::$share->setStorage($storage);

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

    /**
     * @depends testGetShare
     */
    public function testGetShares()
    {
        $this->getShares();
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

    public function _listSharesSystem()
    {
        // Guest shares.
        $shares = self::$share->listShares(false, array('perm' => Horde_Perms::SHOW, 'sort_by' => 'id'));
        //@todo: INTERFACE!!!
        $this->assertEquals(
            array('myshare', 'systemshare'),
            array_keys($shares));
    }

    /**
     * @depends testPermissions
     */
    public function testGetPermission()
    {
        return $this->getPermission();
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

    public function testCallback()
    {
        $this->callback(new Horde_Share_Object_Sql(array()));
    }
}

/**
 NOTES

 - listAllShares() does not really work as expected as we need manager access for that.
 - Permissions are always enforced.
 - listSystemShares not supported yet
 - Why wouldn't the system user see shares from other users?
*/