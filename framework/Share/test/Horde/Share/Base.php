<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Share_Test_Base extends Horde_Test_Case
{
    protected static $share;

    protected static $shares = array();

    public function getApp($app)
    {
        $this->assertEquals($app, self::$share->getApp());
    }

    public function addShare()
    {
        $share = self::$share->newShare('john', 'myshare', 'My Share');
        $this->assertInstanceOf('Horde_Share_Object', $share);
        $share->set('desc', '行事曆');
        $share->addDefaultPermission(Horde_Perms::SHOW);
        $share->addUserPermission('jane', Horde_Perms::SHOW);
        $share->addGroupPermission('mygroup', Horde_Perms::SHOW);
        self::$share->addShare($share);

        // Add a child to the share to test hierarchical functions
        $child = self::$share->newShare('john', 'mychildshare', 'My Child Share');
        $child->set('desc', 'description');
        $this->assertInstanceOf('Horde_Share_Object', $child);
        $child->setParent($share);
        $child->save();

        return $share;
    }

    public function permissions()
    {
        $this->switchAuth(null);
        $this->permissionsSystemShare();
        $this->switchAuth('john');
        $this->permissionsChildShare();
        $this->switchAuth('jane');
        $this->permissionsJaneShare();
        $this->permissionsGroupShare();
        $this->permissionsNoShare();
        $this->switchAuth('john');
    }

    protected function permissionsSystemShare()
    {
        // System share.
        $share = self::$share->newShare(null, 'systemshare', 'System Share');
        $this->assertInstanceOf('Horde_Perms_Permission', $share->getPermission());
        $share->addDefaultPermission(Horde_Perms::SHOW | Horde_Perms::READ);
        $share->addGuestPermission(Horde_Perms::SHOW);
        $share->save();
        $this->assertTrue($share->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($share->hasPermission('john', Horde_Perms::READ));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::EDIT));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::DELETE));
    }

    protected function permissionsChildShare()
    {
        // Child share
        $childshare = self::$share->getShare('mychildshare');
    }

    protected function permissionsJaneShare()
    {
        // Foreign share with user permissions.
        $janeshare = self::$share->newShare('jane', 'janeshare', 'Jane\'s Share');
        $janeshare->addUserPermission('john', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT);
        $janeshare->addUserPermission('peter', Horde_Perms::SHOW);
        $janeshare->save();
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::READ));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::EDIT));
        $this->assertFalse($janeshare->hasPermission('john', Horde_Perms::DELETE));
        $this->assertTrue($janeshare->hasPermission('peter', Horde_Perms::SHOW));
    }

    protected function permissionsGroupShare()
    {
        // Foreign share with group permissions.
        $groupshare = self::$share->newShare('jane', 'groupshare', 'Group Share');
        $groupshare->addGroupPermission('mygroup', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::DELETE);
        $groupshare->save();
        $this->assertTrue($groupshare->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($groupshare->hasPermission('john', Horde_Perms::READ));
        $this->assertFalse($groupshare->hasPermission('john', Horde_Perms::EDIT));
        $this->assertTrue($groupshare->hasPermission('john', Horde_Perms::DELETE));
    }

    protected function permissionsNoShare()
    {
        // Foreign share without permissions.
        $fshare = self::$share->newShare('jane', 'noshare', 'No Share');
        $fshare->save();
    }

    public function exists()
    {
        // Getting shares from cache.
        $this->assertTrue(self::$share->exists('myshare'));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertTrue(self::$share->exists('myshare'));
    }

    public function countShares()
    {
        // Getting shares from cache.
        $this->assertEquals(5, self::$share->countShares('john'));
        // Top level only.
        $this->assertEquals(2, self::$share->countShares('john', Horde_Perms::EDIT, null, null, false));
        $this->assertEquals(3, self::$share->countShares('john', Horde_Perms::EDIT));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(5, self::$share->countShares('john'));
        $this->assertEquals(2, self::$share->countShares('john', Horde_Perms::EDIT, null, null, false));
        $this->assertEquals(3, self::$share->countShares('john', Horde_Perms::EDIT));
    }

    public function hierarchy()
    {
        $share = self::$share->getShare('myshare');
        $child = self::$share->getShare('mychildshare');
        $this->assertEquals($share->getId(), $child->getParent()->getId());
        $this->assertEquals(1, $share->countChildren('john'));
        $this->assertContains($child->getName(), array_keys($share->getChildren('john')));
    }

    public function getShare()
    {
        // Getting shares from cache.
        $share = self::$share->getShare('myshare');
        $this->assertInstanceOf('Horde_Share_Object', $share);
        try {
            self::$share->getShare('nonexistant');
            $this->fail('Share "nonexistant" was expected to not exist.');
        } catch (Horde_Exception_NotFound $e) {
        }

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $share = self::$share->getShare('myshare');
        $this->assertInstanceOf('Horde_Share_Object', $share);

        self::$shares['myshare'] = $share;
        self::$shares['systemshare'] = self::$share->getShare('systemshare');
        self::$shares['janeshare'] = self::$share->getShare('janeshare');
        self::$shares['janeshare']->getPermission();
        self::$shares['groupshare'] = self::$share->getShare('groupshare');
        self::$shares['groupshare']->getPermission();

        $this->switchAuth('jane');
        self::$shares['jane']['janeshare'] = self::$share->getShare('janeshare');
        self::$shares['jane']['groupshare'] = self::$share->getShare('groupshare');

        $this->switchAuth(null);
        self::$shares['system']['systemshare'] = self::$share->getShare('systemshare');
        $this->switchAuth('john');

        return $share;
    }

    public function getShareById()
    {
        // Getting shares from cache.
        $this->_getShareById();
        try {
            self::$share->getShareById(99999);
            $this->fail('Share 99999 was expected to not exist.');
        } catch (Horde_Exception_NotFound $e) {
        }

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_getShareById();
    }

    protected function _getShareById()
    {
        $myshare = self::$share->getShareById(self::$shares['myshare']->getId());
        $this->assertInstanceOf('Horde_Share_Object', $myshare);
        $this->assertEquals(self::$shares['myshare'], $myshare);
        $this->assertEquals('行事曆', $myshare->get('desc'));

        $this->switchAuth('jane');
        $janeshare = self::$share->getShareById(self::$shares['janeshare']->getId());
        $janeshare->getPermission();
        $this->assertInstanceOf('Horde_Share_Object', $janeshare);
        $this->assertEquals(self::$shares['jane']['janeshare'], $janeshare);
        $users = $janeshare->listUsers();
        sort($users);
        $this->assertEquals(array('jane', 'john', 'peter'), $users);
        $this->assertEquals(array('john', 'jane'), $janeshare->listUsers(Horde_Perms::EDIT));
        $this->assertEquals(array('jane'), $janeshare->listUsers(Horde_Perms::DELETE));
        $this->assertEquals('Jane\'s Share', $janeshare->get('name'));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::EDIT));

        $groupshare = self::$share->getShareById(self::$shares['groupshare']->getId());
        $groupshare->getPermission();
        $this->assertInstanceOf('Horde_Share_Object', $groupshare);
        $this->assertEquals(self::$shares['jane']['groupshare'], $groupshare);
        $this->assertEquals(array('mygroup'), $groupshare->listGroups());
        $this->assertEquals(array(), $groupshare->listGroups(Horde_Perms::EDIT));
        $this->assertEquals(array('mygroup'), $groupshare->listGroups(Horde_Perms::DELETE));
        $this->assertEquals('Group Share', $groupshare->get('name'));

        $this->switchAuth('john');
    }

    public function getShares()
    {
        // Getting shares from cache.
        $this->_getShares();

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_getShares();
    }

    protected function _getShares()
    {
        $newshares = self::$share->getShares(array(self::$shares['myshare']->getId(), self::$shares['janeshare']->getId(), self::$shares['groupshare']->getId()));
        $this->assertEquals(
            array('myshare', 'janeshare', 'groupshare'),
            array_keys($newshares));
        $this->assertInstanceOf('Horde_Share_Object', $newshares['myshare']);
        $this->assertEquals(self::$shares['myshare'], $newshares['myshare']);
        $newshares['janeshare']->getPermission();
        $this->assertEquals(self::$shares['janeshare'], $newshares['janeshare']);
        $newshares['groupshare']->getPermission();
        $this->assertEquals(self::$shares['groupshare'], $newshares['groupshare']);
    }

    public function listAllShares()
    {
        // Getting shares from cache.
        $this->_listAllShares();

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_listAllShares();
    }

    protected function _listAllShares()
    {
        $this->switchAuth(null);
        $shares = self::$share->listAllShares();
        $this->assertInternalType('array', $shares);
        $this->assertEquals(6, count($shares));
        $this->assertArrayHasKey('myshare', $shares);
        $this->assertArrayHasKey('systemshare', $shares);
        $this->assertArrayHasKey('janeshare', $shares);
        $this->assertArrayHasKey('groupshare', $shares);
        $this->assertArrayHasKey('noshare', $shares);
        $this->switchAuth('john');
    }

    public function listShares()
    {
        // Getting shares from cache.
        $this->_listShares();

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_listShares();
    }

    public function _listShares()
    {
        $this->_listSharesJohn();
        $this->_listSharesGuest();
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
        $this->assertSortedById(
            array('myshare', 'systemshare', 'janeshare', 'groupshare'),
            $shares);

        // Shares of a certain owner.
        $shares = self::$share->listShares('john', array('attributes' => 'jane', 'sort_by' => 'id'));
        $this->assertSortedById(
            array('janeshare', 'groupshare'),
            $shares);
    }

    public function _listSharesGuest()
    {
        $this->switchAuth(null);

        // Guest shares.
        $shares = self::$share->listShares(false, array('perm' => Horde_Perms::SHOW, 'sort_by' => 'id'));
        $this->assertEquals(
            array('systemshare'),
            array_keys($shares));

        $this->switchAuth('john');
    }

    public function _listSharesJohnTwo()
    {
        // Shares with certain permissions.
        $this->assertEquals(5, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::EDIT, 'sort_by' => 'id'));
        $this->assertSortedById(
            array('myshare', 'mychildshare', 'janeshare'),
            $shares
        );

        // Again with only toplevel
        $shares = self::$share->listShares('john', array('all_levels' => false, 'perm' => Horde_Perms::EDIT, 'sort_by' => 'id'));
        $this->assertSortedById(
            array('myshare', 'janeshare'),
            $shares
        );

        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::DELETE, 'sort_by' => 'id'));
        $this->assertSortedById(
            array('myshare', 'mychildshare', 'groupshare'),
            $shares
        );

        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::EDIT | Horde_Perms::DELETE, 'sort_by' => 'id'));
        $this->assertSortedById(
            array('myshare', 'mychildshare', 'janeshare', 'groupshare'),
            $shares
        );
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL));
        $this->assertInternalType('array', $shares);
        $this->assertEquals(5, count($shares));

        // Paging.
        $all_shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id'));
        $this->assertSortedById(
            array('janeshare', 'groupshare', 'myshare', 'mychildshare', 'systemshare'),
            $all_shares);
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'from' => 2, 'count' => 2));
        $this->assertEquals(
            array_slice(array_keys($all_shares), 2, 2),
            array_keys($shares));

        // Paging with top level only
        $all_top_shares = self::$share->listShares('john', array('all_levels' => false, 'perm' => Horde_Perms::ALL, 'sort_by' => 'id'));
        $this->assertSortedById(
            array('janeshare', 'groupshare', 'myshare', 'systemshare'),
            $all_top_shares);
        $shares = self::$share->listShares('john', array('all_levels' => false, 'perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'from' => 2, 'count' => 2));
        $this->assertEquals(
            array_slice(array_keys($all_top_shares), 2, 2),
            array_keys($shares));

        // Restrict to children of a share only
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'parent' => self::$shares['myshare']));
        $this->assertEquals(
            array('mychildshare'),
            array_keys($shares));

        // Sort order and direction.
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'direction' => 1));
        $this->assertSortedById(
            array('groupshare', 'janeshare', 'systemshare', 'mychildshare', 'myshare'),
            array_reverse($shares));

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

    public function listSystemShares()
    {
        // Getting shares from cache.
        $this->_listSystemShares();

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_listSystemShares();
    }

    public function _listSystemShares()
    {
        $this->switchAuth(null);
        $shares = self::$share->listSystemShares();
        $this->assertInternalType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertArrayHasKey('systemshare', $shares);
        $this->switchAuth('john');
    }

    public function getPermission()
    {
        $permission = self::$shares['myshare']->getPermission();
        $this->assertEquals(Horde_Perms::SHOW, $permission->getDefaultPermissions());
        $this->assertFalse((bool) $permission->getGuestPermissions());
        $this->assertEquals(array('jane' => Horde_Perms::SHOW), $permission->getUserPermissions());
        $this->assertEquals(array('mygroup' => Horde_Perms::SHOW), $permission->getGroupPermissions());
        self::$share->resetCache();

        $permission = self::$share->getShare('myshare')->getPermission();
        $this->assertEquals(Horde_Perms::SHOW, $permission->getDefaultPermissions());
        $this->assertFalse((bool) $permission->getGuestPermissions());
        $this->assertEquals(array('jane' => Horde_Perms::SHOW), $permission->getUserPermissions());
        $this->assertEquals(array('mygroup' => Horde_Perms::SHOW), $permission->getGroupPermissions());
        self::$share->resetCache();

        $shares = self::$share->getShares(array(self::$shares['myshare']->getId()));
        $permission = $shares['myshare']->getPermission();
        $this->assertEquals(Horde_Perms::SHOW, $permission->getDefaultPermissions());
        $this->assertFalse((bool) $permission->getGuestPermissions());
        $this->assertEquals(array('jane' => Horde_Perms::SHOW), $permission->getUserPermissions());
        $this->assertEquals(array('mygroup' => Horde_Perms::SHOW), $permission->getGroupPermissions());
        self::$share->resetCache();

        $shares = self::$share->listShares('john');
        $permission = $shares['myshare']->getPermission();
        $this->assertEquals(Horde_Perms::SHOW, $permission->getDefaultPermissions());
        $this->assertFalse((bool) $permission->getGuestPermissions());
        $this->assertEquals(array('jane' => Horde_Perms::SHOW), $permission->getUserPermissions());
        $this->assertEquals(array('mygroup' => Horde_Perms::SHOW), $permission->getGroupPermissions());

        $permission = self::$shares['system']['systemshare']->getPermission();
        $this->assertEquals(Horde_Perms::SHOW  | Horde_Perms::READ, $permission->getDefaultPermissions());
        $this->assertEquals(Horde_Perms::SHOW, $permission->getGuestPermissions());

        $permission = self::$shares['jane']['janeshare']->getPermission();
        $this->assertEquals(array('john' => Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT, 'peter' => Horde_Perms::SHOW), $permission->getUserPermissions());

        $permission = self::$shares['jane']['groupshare']->getPermission();
        $this->assertEquals(array('mygroup' => Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::DELETE), $permission->getGroupPermissions());

        $this->switchAuth('john');
    }

    public function removeUserPermissions()
    {
        $this->removeUserPermissionsJane();
        $this->switchAuth('john');
        $this->removeUserPermissionsJohn();
    }

    protected function removeUserPermissionsJane()
    {
        $janeshare = self::$shares['jane']['janeshare'];
        $janeshare->removeUserPermission('john', Horde_Perms::EDIT);
        $janeshare->save();

        $this->switchAuth('john');
        // Getting shares from cache.
        $this->assertEquals(5, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(2, count(self::$share->listShares('john', array('perm' => Horde_Perms::EDIT))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(5, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(2, count(self::$share->listShares('john', array('perm' => Horde_Perms::EDIT))));

        $janeshare->removeUser('john');
        $janeshare->save();
    }

    protected function removeUserPermissionsJohn()
    {
        // Getting shares from cache.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
    }

    public function removeGroupPermissions()
    {
        $groupshare = self::$shares['jane']['groupshare'];
        $this->removeGroupPermissionsJane($groupshare);
        $this->removeGroupPermissionsJohn();
        $this->removeGroupPermissionsJaneTwo($groupshare);
        $this->removeGroupPermissionsJohnTwo();
    }

    public function removeGroupPermissionsJane($groupshare)
    {
        $groupshare->removeGroupPermission('mygroup', Horde_Perms::DELETE);
        $groupshare->save();
    }

    public function removeGroupPermissionsJohn()
    {
        $this->switchAuth('john');
        // Getting shares from cache.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(2, count(self::$share->listShares('john', array('perm' => Horde_Perms::DELETE))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(2, count(self::$share->listShares('john', array('perm' => Horde_Perms::DELETE))));
    }

    public function removeGroupPermissionsJaneTwo($groupshare)
    {
        $groupshare->removeGroup('mygroup');
        $groupshare->save();
    }

    public function removeGroupPermissionsJohnTwo()
    {
        $this->switchAuth('john');
        // Getting shares from cache.
        $this->assertEquals(3, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(3, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
    }

    public function removeShare()
    {
        self::$share->removeShare(self::$shares['myshare']);
        try {
            self::$share->getShareById(self::$shares['myshare']->getId());
            $this->fail('Share "myshare" should be removed by now.');
        } catch (Horde_Exception_NotFound $e) {
        }
    }

    public function callback($share)
    {
        $share->setShareOb(new Horde_Support_Stub());
        $this->assertEquals($share, unserialize(serialize($share)));
    }


    protected function assertSortedById($expected, $shares)
    {
        $sort = array();
        foreach ($shares as $key => $share) {
            $sort[$share->getId()] = $key;
        }
        ksort($sort);
        $keys = array_keys($shares);
        $this->assertEquals($keys, array_values($sort));
        sort($expected);
        sort($keys);
        $this->assertEquals($expected, $keys);
    }

    protected function switchAuth($user)
    {
    }
}