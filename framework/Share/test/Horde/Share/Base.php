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
        $share = self::$share->newShare('john', 'myshare');
        $share->set('name', 'My Share');
        $share->set('desc', '行事曆');
        $this->assertInstanceOf('Horde_Share_Object', $share);
        self::$share->addShare($share);

        // Add a child to the share to test hierarchical functions
        $child = self::$share->newShare('john', 'mychildshare');
        $child->set('name', 'My Child Share');
        $child->set('desc', 'description');
        $this->assertInstanceOf('Horde_Share_Object', $child);
        $child->setParent($share);
        $child->save();

        return $share;
    }

    public function permissions()
    {
        // System share.
        $share = self::$share->newShare(null, 'systemshare');
        $share->set('name', 'System Share');
        $perm = $share->getPermission();
        $this->assertInstanceOf('Horde_Perms_Permission', $perm);
        $perm->addDefaultPermission(Horde_Perms::SHOW | Horde_Perms::READ);
        $perm->addGuestPermission(Horde_Perms::SHOW);
        $share->setPermission($perm);
        $share->save();
        $this->assertTrue($share->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($share->hasPermission('john', Horde_Perms::READ));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::EDIT));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::DELETE));

        // Child share
        $childshare = self::$share->getShare('mychildshare');

        // Foreign share with user permissions.
        $janeshare = self::$share->newShare('jane', 'janeshare');
        $janeshare->set('name', 'Jane\'s Share');
        $janeshare->addUserPermission('john', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT);
        $janeshare->save();
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::READ));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::EDIT));
        $this->assertFalse($janeshare->hasPermission('john', Horde_Perms::DELETE));

        // Foreign share with group permissions.
        $groupshare = self::$share->newShare('jane', 'groupshare');
        $groupshare->set('name', 'Group Share');
        $groupshare->addGroupPermission('mygroup', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::DELETE);
        $groupshare->save();
        $this->assertTrue($groupshare->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($groupshare->hasPermission('john', Horde_Perms::READ));
        $this->assertFalse($groupshare->hasPermission('john', Horde_Perms::EDIT));
        $this->assertTrue($groupshare->hasPermission('john', Horde_Perms::DELETE));

        // Foreign share without permissions.
        $fshare = self::$share->newShare('jane', 'noshare');
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
        self::$shares['groupshare'] = self::$share->getShare('groupshare');

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

        $janeshare = self::$share->getShareById(self::$shares['janeshare']->getId());
        $this->assertInstanceOf('Horde_Share_Object', $janeshare);
        $this->assertEquals(self::$shares['janeshare'], $janeshare);
        $this->assertEquals(array('john', 'jane'), $janeshare->listUsers());
        $this->assertEquals(array('john', 'jane'), $janeshare->listUsers(Horde_Perms::EDIT));
        $this->assertEquals(array('jane'), $janeshare->listUsers(Horde_Perms::DELETE));
        $this->assertEquals('Jane\'s Share', $janeshare->get('name'));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::EDIT));
        $this->assertTrue($janeshare->hasPermission('jane', 99999));

        $groupshare = self::$share->getShareById(self::$shares['groupshare']->getId());
        $this->assertInstanceOf('Horde_Share_Object', $groupshare);
        $this->assertEquals(self::$shares['groupshare'], $groupshare);
        $this->assertEquals(array('mygroup'), $groupshare->listGroups());
        $this->assertEquals(array(), $groupshare->listGroups(Horde_Perms::EDIT));
        $this->assertEquals(array('mygroup'), $groupshare->listGroups(Horde_Perms::DELETE));
        $this->assertEquals('Group Share', $groupshare->get('name'));
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
        $this->assertEquals(self::$shares['janeshare'], $newshares['janeshare']);
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
        $shares = self::$share->listAllShares();
        $this->assertInternalType('array', $shares);
        $this->assertEquals(6, count($shares));
        $this->assertArrayHasKey('myshare', $shares);
        $this->assertArrayHasKey('systemshare', $shares);
        $this->assertArrayHasKey('janeshare', $shares);
        $this->assertArrayHasKey('groupshare', $shares);
        $this->assertArrayHasKey('noshare', $shares);
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
        // Default listing.
        $shares = self::$share->listShares('john');
        $this->assertInternalType('array', $shares);
        $this->assertEquals(5, count($shares));

        // Test arguments for default listing.
        $this->assertEquals($shares, self::$share->listShares('john', array('perm' => Horde_Perms::SHOW, 'attributes' => null, 'from' => 0, 'count' => 0, 'sort_by' => null, 'direction' => 0)));

        // Getting back the correct shares?
        $shares = self::$share->listShares('john', array('all_levels' => false, 'sort_by' => 'id'));
        $this->assertEquals(
            array('myshare', 'systemshare', 'janeshare', 'groupshare'),
            array_keys($shares));

        // Shares of a certain owner.
        $shares = self::$share->listShares('john', array('attributes' => 'jane', 'sort_by' => 'id'));
        $this->assertEquals(
            array('janeshare', 'groupshare'),
            array_keys($shares));

        // Guest shares.
        $share = self::$share->getShareById(2);
        $shares = self::$share->listShares(false, array('perm' => Horde_Perms::SHOW, 'sort_by' => 'id'));
        $this->assertEquals(
            array('systemshare'),
            array_keys($shares));

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
        $this->assertEquals(
            array('myshare', 'mychildshare', 'janeshare', 'groupshare'),
            array_keys($shares));
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL));
        $this->assertInternalType('array', $shares);
        $this->assertEquals(5, count($shares));

        // Paging.
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'from' => 2, 'count' => 2));
        $this->assertEquals(
            array('systemshare', 'janeshare'),
            array_keys($shares));

        // Paging with top level only
        $shares = self::$share->listShares('john', array('all_levels' => false, 'perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'from' => 2, 'count' => 2));
        $this->assertEquals(
            array('janeshare', 'groupshare'),
            array_keys($shares));

        // Restrict to children of a share only
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'parent' => self::$shares['myshare']));
        $this->assertEquals(
            array('mychildshare'),
            array_keys($shares));

        // Sort order and direction.
        $shares = self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'direction' => 1));
        $this->assertEquals(
            array('groupshare', 'janeshare', 'systemshare', 'mychildshare', 'myshare'),
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
        $shares = self::$share->listSystemShares();
        $this->assertInternalType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertArrayHasKey('systemshare', $shares);
    }

    public function getPermission()
    {
        $permission = new Horde_Perms_Permission('systemshare');
        $permission->addDefaultPermission(Horde_Perms::SHOW | Horde_Perms::READ);
        $permission->addGuestPermission(Horde_Perms::SHOW);
        $permission->addCreatorPermission(0);
        $this->assertEquals($permission, self::$shares['systemshare']->getPermission());
        $permission = new Horde_Perms_Permission('janeshare');
        $permission->addDefaultPermission(0);
        $permission->addGuestPermission(0);
        $permission->addCreatorPermission(0);
        $permission->addUserPermission('john', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT);
        $this->assertEquals($permission, self::$shares['janeshare']->getPermission());
        $permission = new Horde_Perms_Permission('groupshare');
        $permission->addDefaultPermission(0);
        $permission->addGuestPermission(0);
        $permission->addCreatorPermission(0);
        $permission->addGroupPermission('mygroup', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::DELETE);
        $this->assertEquals($permission, self::$shares['groupshare']->getPermission());
    }

    public function removeUserPermissions()
    {
        $janeshare = self::$shares['janeshare'];
        $janeshare->removeUserPermission('john', Horde_Perms::EDIT);
        $janeshare->save();

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

        // Getting shares from cache.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
    }

    public function removeGroupPermissions()
    {
        $groupshare = self::$shares['groupshare'];
        $groupshare->removeGroupPermission('mygroup', Horde_Perms::DELETE);
        $groupshare->save();

        // Getting shares from cache.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(2, count(self::$share->listShares('john', array('perm' => Horde_Perms::DELETE))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(2, count(self::$share->listShares('john', array('perm' => Horde_Perms::DELETE))));

        $groupshare->removeGroup('mygroup');
        $groupshare->save();

        // Getting shares from cache.
        $this->assertEquals(3, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(3, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
    }

    public function removeShare()
    {
        // Getting shares from cache.
        $this->_removeShare();

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_removeShare();
    }

    public function _removeShare()
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
}