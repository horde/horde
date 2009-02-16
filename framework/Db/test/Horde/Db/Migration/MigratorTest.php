<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @group      horde_db
 * @category   Horde
 * @package    Horde_Db
 * @subpackage UnitTests
 */
class Horde_Db_Migration_MigratorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Horde_Db_Migration_Base::$verbose = false;
    }

    public function tearDown()
    {
        $this->_conn->initializeSchemaInformation();
        $this->_conn->update("UPDATE schema_info SET version = 0");

        // drop tables
        foreach (array('reminders', 'users_reminders', 'testings', 'octopuses',
                       'octopi', 'binary_testings', 'big_numbers') as $table) {
            try {
                $this->_conn->dropTable($table);
            } catch (Exception $e) {}
        }

        // drop cols
        foreach (array('first_name', 'middle_name', 'last_name', 'key', 'male',
                       'bio', 'age', 'height', 'wealth', 'birthday', 'group',
                       'favorite_day', 'moment_of_truth', 'administrator',
                       'exgirlfriend', 'contributor', 'nick_name',
                       'intelligence_quotient') as $col) {
            try {
                $this->_conn->removeColumn('users', $col);
            } catch (Exception $e) {}
        }
        $this->_conn->addColumn('users', 'first_name', 'string', array('limit' => 40));
        $this->_conn->changeColumn('users', 'approved', 'boolean', array('default' => true));
    }

    public function testMigrator()
    {
        $user = new User;
        $columns = $user->columnNames();

        $this->assertFalse(in_array('last_name', $columns));

        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);

        $dir = dirname(dirname(dirname(dirname(__FILE__)))).'/fixtures/migrations/';
        Horde_Db_Migration_Migrator::up($dir);
        $this->assertEquals(3, Horde_Db_Migration_Migrator::getCurrentVersion());

        $user->resetColumnInformation();
        $columns = $user->columnNames();
        $this->assertTrue(in_array('last_name', $columns));

        $result = Reminder::create(array('content'   => 'hello world',
                                         'remind_at' => '2005-01-01 02:22:23'));
        $reminder = Reminder::find('first');
        $this->assertEquals('hello world', $reminder->content);

        $dir = dirname(dirname(dirname(dirname(__FILE__)))).'/fixtures/migrations/';
        Horde_Db_Migration_Migrator::down($dir);
        $this->assertEquals(0, Horde_Db_Migration_Migrator::getCurrentVersion());

        $user->resetColumnInformation();
        $columns = $user->columnNames();
        $this->assertFalse(in_array('last_name', $columns));

        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);
    }

    public function testOneUp()
    {
        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);

        $dir = dirname(dirname(dirname(dirname(__FILE__)))).'/fixtures/migrations/';
        Horde_Db_Migration_Migrator::up($dir, 1);
        $this->assertEquals(1, Horde_Db_Migration_Migrator::getCurrentVersion());

        $user = new User;
        $columns = $user->columnNames();
        $this->assertTrue(in_array('last_name', $columns));

        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);

        Horde_Db_Migration_Migrator::up($dir, 2);
        $this->assertEquals(2, Horde_Db_Migration_Migrator::getCurrentVersion());

        $result = Reminder::create(array('content'   => 'hello world',
                                         'remind_at' => '2005-01-01 02:22:23'));
        $reminder = Reminder::find('first');
        $this->assertEquals('hello world', $reminder->content);
    }

    public function testOneDown()
    {
        $dir = dirname(dirname(dirname(dirname(__FILE__)))).'/fixtures/migrations/';

        Horde_Db_Migration_Migrator::up($dir);
        Horde_Db_Migration_Migrator::down($dir, 1);

        $user = new User;
        $columns = $user->columnNames();
        $this->assertTrue(in_array('last_name', $columns));
    }

    public function testOneUpOneDown()
    {
        $dir = dirname(dirname(dirname(dirname(__FILE__)))).'/fixtures/migrations/';

        Horde_Db_Migration_Migrator::up($dir, 1);
        Horde_Db_Migration_Migrator::down($dir, 0);

        $user = new User;
        $columns = $user->columnNames();
        $this->assertFalse(in_array('last_name', $columns));
    }

    public function testMigratorGoingDownDueToVersionTarget()
    {
        $dir = dirname(dirname(dirname(dirname(__FILE__)))).'/fixtures/migrations/';

        Horde_Db_Migration_Migrator::up($dir, 1);
        Horde_Db_Migration_Migrator::down($dir, 0);

        $user = new User;
        $columns = $user->columnNames();
        $this->assertFalse(in_array('last_name', $columns));

        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);


        Horde_Db_Migration_Migrator::up($dir);

        $user->resetColumnInformation();
        $columns = $user->columnNames();
        $this->assertTrue(in_array('last_name', $columns));

        $result = Reminder::create(array('content'   => 'hello world',
                                         'remind_at' => '2005-01-01 02:22:23'));
        $reminder = Reminder::find('first');
        $this->assertEquals('hello world', $reminder->content);
    }

    public function testWithDuplicates()
    {
        try {
            $dir = dirname(dirname(dirname(dirname(__FILE__)))).'/fixtures/migrations_with_duplicate/';
            Horde_Db_Migration_Migrator::up($dir);
        } catch (Exception $e) { return; }
        $this->fail('Expected exception wasn\'t raised');
    }

    public function testWithMissingVersionNumbers()
    {
        $dir = dirname(dirname(dirname(dirname(__FILE__)))).'/fixtures/migrations_with_missing_versions/';
        Horde_Db_Migration_Migrator::migrate($dir, 500);
        $this->assertEquals(4, Horde_Db_Migration_Migrator::getCurrentVersion());

        Horde_Db_Migration_Migrator::migrate($dir, 2);
        $this->assertEquals(2, Horde_Db_Migration_Migrator::getCurrentVersion());

        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);

        $user = new User;
        $columns = $user->columnNames();
        $this->assertTrue(in_array('last_name', $columns));
    }
}