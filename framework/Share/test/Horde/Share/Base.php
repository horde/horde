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

    public function testGetApp()
    {
        $this->assertEquals('test', self::$share->getApp());
    }

    public function addShare()
    {
        $share = self::$share->newShare('john', 'myshare');
        $share->set('name', 'My Share');
        $share->set('desc', '行事曆');
        $this->assertInstanceOf('Horde_Share_Object', $share);
        self::$share->addShare($share);
        return $share;
    }

    public function permissions($myshareid)
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
        $share = self::$share->newShare('jane', 'noshare');
        $share->save();

        return array($myshareid, $janeshare->getId(), $groupshare->getId());
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
        $this->assertEquals(4, self::$share->countShares('john'));
        $this->assertEquals(2, self::$share->countShares('john', Horde_Perms::EDIT));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(4, self::$share->countShares('john'));
        $this->assertEquals(2, self::$share->countShares('john', Horde_Perms::EDIT));
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

        return array($share, self::$share->getShare('janeshare'), self::$share->getShare('groupshare'));
    }

    public function getShareById(array $shares)
    {
        // Getting shares from cache.
        $this->_getShareById($shares);
        try {
            self::$share->getShareById(99999);
            $this->fail('Share 99999 was expected to not exist.');
        } catch (Horde_Exception_NotFound $e) {
        }

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_getShareById($shares);
    }

    protected function _getShareById(array $shares)
    {
        $myshare = self::$share->getShareById($shares[0]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $myshare);
        $this->assertEquals($shares[0], $myshare);
        $this->assertEquals('行事曆', $myshare->get('desc'));

        $janeshare = self::$share->getShareById($shares[1]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $janeshare);
        $this->assertEquals($shares[1], $janeshare);
        $this->assertEquals(array('john', 'jane'), $janeshare->listUsers());
        $this->assertEquals(array('john', 'jane'), $janeshare->listUsers(Horde_Perms::EDIT));
        $this->assertEquals(array('jane'), $janeshare->listUsers(Horde_Perms::DELETE));
        $this->assertEquals('Jane\'s Share', $janeshare->get('name'));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::EDIT));
        $this->assertTrue($janeshare->hasPermission('jane', 99999));

        $groupshare = self::$share->getShareById($shares[2]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $groupshare);
        $this->assertEquals($shares[2], $groupshare);
        $this->assertEquals(array('mygroup'), $groupshare->listGroups());
        $this->assertEquals(array(), $groupshare->listGroups(Horde_Perms::EDIT));
        $this->assertEquals(array('mygroup'), $groupshare->listGroups(Horde_Perms::DELETE));
        $this->assertEquals('Group Share', $groupshare->get('name'));
    }

    public function getShares(array $shares)
    {
        // Getting shares from cache.
        $this->_getShares($shares);

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_getShares($shares);
    }

    protected function _getShares(array $shares)
    {
        $newshares = self::$share->getShares(array($shares[0]->getId(), $shares[1]->getId(), $shares[2]->getId()));
        $this->assertType('array', $newshares);
        $this->assertEquals(3, count($newshares));
        $this->assertArrayHasKey('myshare', $newshares);
        $this->assertArrayHasKey('janeshare', $newshares);
        $this->assertArrayHasKey('groupshare', $newshares);
        $this->assertInstanceOf('Horde_Share_Object', $newshares['myshare']);
        $this->assertInstanceOf('Horde_Share_Object', $newshares['janeshare']);
        $this->assertInstanceOf('Horde_Share_Object', $newshares['groupshare']);
        $this->assertEquals($newshares['myshare'], $shares[0]);
        $this->assertEquals($newshares['janeshare'], $shares[1]);
        $this->assertEquals($newshares['groupshare'], $shares[2]);
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
        $this->assertType('array', $shares);
        $this->assertEquals(5, count($shares));
        $this->assertArrayHasKey('myshare', $shares);
        $this->assertArrayHasKey('systemshare', $shares);
        $this->assertArrayHasKey('janeshare', $shares);
        $this->assertArrayHasKey('groupshare', $shares);
        $this->assertArrayHasKey('noshare', $shares);
    }

    public function listShares(array $shareids)
    {
        // Getting shares from cache.
        $this->_listShares($shareids);

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_listShares($shareids);
    }

    public function _listShares(array $shareids)
    {
        // Default listing.
        $shares = self::$share->listShares('john');
        $this->assertType('array', $shares);
        $this->assertEquals(4, count($shares));

        // Test arguments for default listing.
        $this->assertEquals($shares, self::$share->listShares('john', array('perm' => Horde_Perms::SHOW, 'attributes' => null, 'from' => 0, 'count' => 0, 'sort_by' => null, 'direction' => 0)));

        // Getting back the correct shares?
        $shares = array_values(self::$share->listShares('john', array('sort_by' => 'id')));
        $this->assertEquals($shareids[0], $shares[0]->getId());
        $this->assertEquals($shareids[1], $shares[2]->getId());
        $this->assertEquals($shareids[2], $shares[3]->getId());

        // Shares of a certain owner.
        $shares = array_values(self::$share->listShares('john', array('attributes' => 'jane', 'sort_by' => 'id')));
        $this->assertType('array', $shares);
        $this->assertEquals(2, count($shares));
        $this->assertEquals($shareids[1], $shares[0]->getId());
        $this->assertEquals($shareids[2], $shares[1]->getId());

        // Guest shares.
        $share = self::$share->getShareById(2);
        $shares = array_values(self::$share->listShares(false, array('perm' => Horde_Perms::SHOW, 'sort_by' => 'id')));
        $this->assertType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertEquals('System Share', $shares[0]->get('name'));

        // Shares with certain permissions.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $shares = array_values(self::$share->listShares('john', array('perm' => Horde_Perms::EDIT, 'sort_by' => 'id')));
        $this->assertType('array', $shares);
        $this->assertEquals(2, count($shares));
        $this->assertEquals($shareids[0], $shares[0]->getId());
        $this->assertEquals($shareids[1], $shares[1]->getId());
        $shares = array_values(self::$share->listShares('john', array('perm' => Horde_Perms::DELETE, 'sort_by' => 'id')));
        $this->assertType('array', $shares);
        $this->assertEquals(2, count($shares));
        $this->assertEquals($shareids[0], $shares[0]->getId());
        $this->assertEquals($shareids[2], $shares[1]->getId());
        $shares = array_values(self::$share->listShares('john', array('perm' => Horde_Perms::EDIT | Horde_Perms::DELETE)));
        $this->assertType('array', $shares);
        $this->assertEquals(3, count($shares));
        $shares = array_values(self::$share->listShares('john', array('perm' => Horde_Perms::ALL)));
        $this->assertType('array', $shares);
        $this->assertEquals(4, count($shares));

        // Paging.
        $shares = array_values(self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'from' => 2, 'count' => 2)));
        $this->assertType('array', $shares);
        $this->assertEquals(2, count($shares));
        $this->assertEquals($shareids[1], $shares[0]->getId());
        $this->assertEquals($shareids[2], $shares[1]->getId());

        // Sort order and direction.
        $shares = array_values(self::$share->listShares('john', array('perm' => Horde_Perms::ALL, 'sort_by' => 'id', 'direction' => 1)));
        $this->assertType('array', $shares);
        $this->assertEquals(4, count($shares));
        $this->assertEquals($shareids[2], $shares[0]->getId());
        $this->assertEquals($shareids[0], $shares[3]->getId());

        // Attribute searching.
        $shares = array_values(self::$share->listShares('john', array('attributes' => array('name' => 'Jane\'s Share'))));
        $this->assertType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertEquals($shareids[1], $shares[0]->getId());
        $shares = array_values(self::$share->listShares('john', array('attributes' => array('desc' => '行事曆'))));
        $this->assertType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertEquals($shareids[0], $shares[0]->getId());
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
        $this->assertType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertArrayHasKey('systemshare', $shares);
    }

    public function removeUserPermissions(array $shareids)
    {
        $janeshare = self::$share->getShareById($shareids[1]);
        $janeshare->removeUserPermission('john', Horde_Perms::EDIT);
        $janeshare->save();

        // Getting shares from cache.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(1, count(self::$share->listShares('john', array('perm' => Horde_Perms::EDIT))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(4, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(1, count(self::$share->listShares('john', array('perm' => Horde_Perms::EDIT))));

        $janeshare->removeUser('john');
        $janeshare->save();

        // Getting shares from cache.
        $this->assertEquals(3, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(3, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));

        return $shareids;
    }

    public function removeGroupPermissions(array $shareids)
    {
        $groupshare = self::$share->getShareById($shareids[2]);
        $groupshare->removeGroupPermission('mygroup', Horde_Perms::DELETE);
        $groupshare->save();

        // Getting shares from cache.
        $this->assertEquals(3, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(1, count(self::$share->listShares('john', array('perm' => Horde_Perms::DELETE))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(3, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
        $this->assertEquals(1, count(self::$share->listShares('john', array('perm' => Horde_Perms::DELETE))));

        $groupshare->removeGroup('mygroup');
        $groupshare->save();

        // Getting shares from cache.
        $this->assertEquals(2, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(2, count(self::$share->listShares('john', array('perm' => Horde_Perms::READ))));
    }

    public function removeShare(array $share)
    {
        // Getting shares from cache.
        $this->_removeShare($share);

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->_removeShare($share);
    }

    public function _removeShare(array $share)
    {
        self::$share->removeShare($share[0]);
        try {
            self::$share->getShareById($share[0]->getId());
            $this->fail('Share ' . $share[0]->getId() . ' should be removed by now.');
        } catch (Horde_Exception_NotFound $e) {
        }
    }

    public function callback($share)
    {
        $share->setShareOb(new Horde_Support_Stub());
        $this->assertEquals($share, unserialize(serialize($share)));
    }
}