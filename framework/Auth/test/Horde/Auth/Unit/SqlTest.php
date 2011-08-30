<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * @author     Ralf Lang <lang@ralf-lang.de>
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 */

class Horde_Auth_Unit_SqlTest extends Horde_Auth_TestCase
{

    protected function setUp() 
    {
        $db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));

// Move to fixture file or maybe wrap PHPUnit_Extensions_Database_TestCase?
// Sqlite is picky with multiple statements in one execute
$table = "CREATE TABLE horde_users ( user_uid VARCHAR(255) PRIMARY KEY NOT NULL,
                    user_pass VARCHAR(255) NOT NULL,
                    user_soft_expiration_date INTEGER,
                    user_hard_expiration_date INTEGER
                 );";

$db->execute($table);
$table = '
CREATE TABLE horde_locks (
  lock_id varchar(36) PRIMARY KEY NOT NULL,
  lock_owner varchar(32) NOT NULL,
  lock_scope varchar(32) NOT NULL,
  lock_principal varchar(255) NOT NULL,
  lock_origin_timestamp bigint(20) NOT NULL,
  lock_update_timestamp bigint(20) NOT NULL,
  lock_expiry_timestamp bigint(20) NOT NULL,
  lock_type smallint(5) NOT NULL
);';
$db->execute($table);
$row = "INSERT INTO horde_users VALUES ('mozilla', 'liketokyo', NULL, NULL);";
$db->execute($row);
$row = "INSERT INTO horde_users VALUES ('konqui', 'kde', NULL, NULL);";
$db->execute($row);
$row = "INSERT INTO horde_users VALUES ('tux', 'fish', NULL, NULL);";
$db->execute($row);
// $row = "INSERT INTO horde_locks VALUES (1, 'konqui', 'horde_auth', 'login:konqui', 4294967295, 4294967295, 4294967295, 1);";
// $db->execute($row);


        $this->db = $db;
        $this->locks = new Horde_Lock_Sql(array('db' => $db));
        $this->driverHasNoLock = new Horde_Auth_Sql(array('db' => $db, 'encryption' => 'plain'));
        $this->driverHasLock = new Horde_Auth_Sql(array('db' => $db, 'encryption' => 'plain', 'lock_api' => $this->locks));
    }

    public function testAuthenticate()
    {
        $this->assertTrue($this->driverHasNoLock->authenticate('tux', array('password' => 'fish')));
    }

    public function testListUsers()
    {
        $resultUnsorted = $this->driverHasNoLock->listUsers();
        sort($resultUnsorted);
        $this->assertEquals(array('konqui', 'mozilla', 'tux'), $resultUnsorted);
    }
    public function testListUsersWithSorting()
    {
        $this->assertEquals(array('konqui', 'mozilla', 'tux'), $this->driverHasNoLock->listUsers(true));
    }

    public function testPresentLockApiTriggersLockCapability()
    {
        $this->assertTrue($this->driverHasLock->hasCapability('lock'));
    }
    public function testMissingLockApiNoLockCapability()
    {
        $this->assertFalse($this->driverHasNoLock->hasCapability('lock'));
    }

    /**
     * @expectedException Horde_Auth_Exception
     */

    public function testLockUserTwiceFails()
    {
        $this->driverHasLock->lockUser('konqui');
        $this->driverHasLock->lockUser('konqui');
    }

    public function testLockedUserReportsAsLocked()
    {
        $this->driverHasLock->lockUser('konqui');
        $this->assertTrue($this->driverHasLock->isLocked('konqui'));
    }

    public function testLockedUserCannotLogin()
    {
        $this->driverHasLock->lockUser('konqui');
        $this->assertFalse($this->driverHasLock->authenticate('konqui', array('password' => 'kde')));
    }

    public function testUnlockUnlockedDoesNotThrowException()
    {
        $this->driverHasLock->unlockUser('konqui');
        $this->driverHasLock->unlockUser('konqui');
        $this->driverHasLock->unlockUser('konqui');
    }

}
