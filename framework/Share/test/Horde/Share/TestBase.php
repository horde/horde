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
class Horde_Share_TestBase extends PHPUnit_Framework_TestCase
{
    protected static $share;

    public function testGetApp()
    {
        $this->assertEquals('test', self::$share->getApp());
    }

    public function baseAddShare()
    {
        $share = self::$share->newShare('john', 'myshare');
        $this->assertInstanceOf('Horde_Share_Object', $share);
        self::$share->addShare($share);
        return $share;
    }

    public function basePermissions($myshareid)
    {
        // System share.
        $share = self::$share->newShare(null, 'systemshare');
        $perm = $share->getPermission();
        $this->assertInstanceOf('Horde_Perms_Permission', $perm);
        $perm->addDefaultPermission(Horde_Perms::SHOW | Horde_Perms::READ);
        $share->setPermission($perm);
        $share->save();
        $this->assertTrue($share->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($share->hasPermission('john', Horde_Perms::READ));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::EDIT));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::DELETE));

        // Foreign share with user permissions.
        $janeshare = self::$share->newShare('jane', 'janeshare');
        $janeshare->addUserPermission('john', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT);
        $janeshare->save();
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::READ));
        $this->assertTrue($janeshare->hasPermission('john', Horde_Perms::EDIT));
        $this->assertFalse($janeshare->hasPermission('john', Horde_Perms::DELETE));

        // Foreign share with group permissions.
        $groupshare = self::$share->newShare('jane', 'groupshare');
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

    public function baseExists()
    {
        $this->assertTrue(self::$share->exists('myshare'));

        // Reset cache.
        self::$share->resetCache();

        $this->assertTrue(self::$share->exists('myshare'));
    }

    public function baseCountShares()
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

    public function baseGetShare()
    {
        $share = self::$share->getShare('myshare');
        $this->assertInstanceOf('Horde_Share_Object', $share);

        // Reset cache.
        self::$share->resetCache();

        $share = self::$share->getShare('myshare');
        $this->assertInstanceOf('Horde_Share_Object', $share);

        return array($share, self::$share->getShare('janeshare'), self::$share->getShare('groupshare'));
    }

    public function baseGetShareById(array $shares)
    {
        $newshare = self::$share->getShareById($shares[0]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $newshare);
        $this->assertEquals($shares[0], $newshare);
        $newshare = self::$share->getShareById($shares[1]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $newshare);
        $this->assertEquals($shares[1], $newshare);
        $newshare = self::$share->getShareById($shares[2]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $newshare);
        $this->assertEquals($shares[2], $newshare);

        // Reset cache.
        self::$share->resetCache();

        $newshare = self::$share->getShareById($shares[0]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $newshare);
        $this->assertEquals($shares[0], $newshare);
        $newshare = self::$share->getShareById($shares[1]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $newshare);
        $this->assertEquals($shares[1], $newshare);
        $newshare = self::$share->getShareById($shares[2]->getId());
        $this->assertInstanceOf('Horde_Share_Object', $newshare);
        $this->assertEquals($shares[2], $newshare);
    }

    public function baseGetShares(array $shares)
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

        // Reset cache.
        self::$share->resetCache();

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

    public function baseListAllShares()
    {
        // Getting shares from cache.
        $shares = self::$share->listAllShares();
        $this->assertType('array', $shares);
        $this->assertEquals(5, count($shares));
        $this->assertArrayHasKey('myshare', $shares);
        $this->assertArrayHasKey('systemshare', $shares);
        $this->assertArrayHasKey('janeshare', $shares);
        $this->assertArrayHasKey('groupshare', $shares);
        $this->assertArrayHasKey('noshare', $shares);

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $shares = self::$share->listAllShares();
        $this->assertType('array', $shares);
        $this->assertEquals(5, count($shares));
        $this->assertArrayHasKey('myshare', $shares);
        $this->assertArrayHasKey('systemshare', $shares);
        $this->assertArrayHasKey('janeshare', $shares);
        $this->assertArrayHasKey('groupshare', $shares);
        $this->assertArrayHasKey('noshare', $shares);
    }

    public function baseListSystemShares()
    {
        // Getting shares from cache.
        $shares = self::$share->listSystemShares();
        $this->assertType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertArrayHasKey('systemshare', $shares);

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $shares = self::$share->listSystemShares();
        $this->assertType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertArrayHasKey('systemshare', $shares);
    }

    public function baseRemoveShare(array $share)
    {
        self::$share->removeShare($share[0]);
        try {
            self::$share->getShareById($share[0]->getId());
            $this->fail('Share ' . $share[0]->getId() . ' should be removed by now.');
        } catch (Horde_Exception_NotFound $e) {
        }

        // Reset cache.
        self::$share->resetCache();

        try {
            self::$share->getShareById($share[0]->getId());
            $this->fail('Share ' . $share[0]->getId() . ' should be removed by now.');
        } catch (Horde_Exception_NotFound $e) {
        }
    }
}

class Horde_Group_Test extends Horde_Group {
    public function __construct()
    {
    }

    public function __wakeup()
    {
    }

    public function userIsInGroup($user, $gid, $subgroups = true)
    {
        return $user == 'john' && $gid == 'mygroup';
    }

    public function getGroupMemberships($user, $parentGroups = false)
    {
        return $user == 'john' ? array('mygroup' => 'mygroup') : array();
    }
}
