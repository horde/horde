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

require_once dirname(dirname(__FILE__)) . '/fixtures/migrations/1_users_have_last_names1.php';
require_once dirname(dirname(__FILE__)) . '/fixtures/migrations/2_we_need_reminders1.php';
require_once dirname(dirname(__FILE__)) . '/fixtures/migrations_with_decimal/1_give_me_big_numbers.php';

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
class Horde_Db_Migration_BaseTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_conn = Horde_Db_Adapter::factory(array(
            'adapter' => 'pdo_sqlite',
            'dbname' => ':memory:',
        ));

        /*
CREATE TABLE users (
  id         int(11) auto_increment,
  company_id int(11),
  name       varchar(255) default '',
  first_name varchar(40) default '',
  approved   tinyint(1) default '1',
  type       varchar(255) default '',
  created_at datetime default '0000-00-00 00:00:00',
  created_on date default '0000-00-00',
  updated_at datetime default '0000-00-00 00:00:00',
  updated_on date default '0000-00-00',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        */
        $table = $this->_conn->createTable('users');
          $table->column('company_id',  'integer',  array('limit' => 11));
          $table->column('name',        'string',   array('limit' => 255, 'default' => ''));
          $table->column('first_name',  'string',   array('limit' => 40, 'default' => ''));
          $table->column('approved',    'boolean',  array('default' => true));
          $table->column('type',        'string',   array('limit' => 255, 'default' => ''));
          $table->column('created_at',  'datetime', array('default' => '0000-00-00 00:00:00'));
          $table->column('created_on',  'date',     array('default' => '0000-00-00'));
          $table->column('updated_at',  'datetime', array('default' => '0000-00-00 00:00:00'));
          $table->column('updated_on',  'date',     array('default' => '0000-00-00'));
        $table->end();
        /*
mike:
  id:         1
  company_id: 1
  name:       Mike Naberezny
  first_name: Mike
  approved:   1
  type:       User
  created_at: '2008-01-01 12:20:00'
  created_on: '2008-01-01'
  updated_at: '2008-01-01 12:20:00'
  updated_on: '2008-01-01'

derek:
  id:         2
  company_id: 1
  name:       Derek DeVries
  first_name: Derek
  approved:   1
  type:       User
  created_at: '<?php echo date("Y-m-d H:i:s", strtotime("-1 day")) ?>'
  created_on: '<?php echo date("Y-m-d",       strtotime("-1 day")) ?>'
  updated_at: '<?php echo date("Y-m-d H:i:s", strtotime("-1 day")) ?>'
  updated_on: '<?php echo date("Y-m-d",       strtotime("-1 day")) ?>'

client:
  id:         3
  company_id: 1
  name:       Extreme
  first_name: Engineer
  approved:   1
  type:       Client
  created_at: '2008-01-01 12:20:00'
  created_on: '2008-01-01'
  updated_at: '2008-01-01 12:20:00'
  updated_on: '2008-01-01'
        */

        Horde_Db_Migration_Base::$verbose = false;
    }

    public function testAddIndex()
    {
        // Limit size of last_name and key columns to support Firebird index limitations
        $this->_conn->addColumn('users', 'last_name',     'string',  array('limit' => 100));
        $this->_conn->addColumn('users', 'key',           'string',  array('limit' => 100));
        $this->_conn->addColumn('users', 'administrator', 'boolean');

        $this->_conn->addIndex('users', 'last_name');
        $this->_conn->removeIndex('users', 'last_name');

        $this->_conn->addIndex('users', array('last_name', 'first_name'));
        $this->_conn->removeIndex('users', array('column' => array('last_name', 'first_name')));

        $this->_conn->addIndex('users', array('last_name', 'first_name'));
        $this->_conn->removeIndex('users', array('name' => 'index_users_on_last_name_and_first_name'));

        $this->_conn->addIndex('users', array('last_name', 'first_name'));
        $this->_conn->removeIndex('users', 'last_name_and_first_name');

        // quoting

        $this->_conn->addIndex('users', array('key'), array('name' => 'key_idx', 'unique' => true));
        $this->_conn->removeIndex('users', array('name' => 'key_idx', 'unique' => true));

        $this->_conn->addIndex('users', array('last_name', 'first_name', 'administrator'),
                                        array('name' => "named_admin"));

        $this->_conn->removeIndex('users', array('name' => 'named_admin'));
    }

    public function testCreateTableAddsId()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('foo', 'string');
        $table->end();

        $columns = array();
        foreach ($this->_conn->columns('testings') as $col) {
            $columns[] = $col->getName();
        }
        sort($columns);
        $this->assertEquals(array('foo', 'id'), $columns);
    }

    public function testCreateTableWithNotNullColumn()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('foo', 'string', array('null' => false));
        $table->end();

        try {
            $this->_conn->execute("INSERT INTO testings (foo) VALUES (NULL)");
        } catch (Exception $e) { return; }
        $this->fail('Expected exception wasn\'t raised');
    }

    /**
     * @see  Horde_Db_Adapter_Abstract_ColumnTest
     */
    public function testCreateTableWithDefaults()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('one',   'string',  array('default' => 'hello'));
            $table->column('two',   'boolean', array('default' => true));
            $table->column('three', 'boolean', array('default' => false));
            $table->column('four',  'integer', array('default' => 1));
        $table->end();

        $columns = array();
        foreach ($this->_conn->columns('testings') as $col) {
            $columns[$col->getName()] = $col;
        }

        $this->assertEquals('hello', $columns['one']->getDefault());

        $this->assertTrue($columns['two']->getDefault());
        $this->assertFalse($columns['three']->getDefault());

        $this->assertEquals(true,     $columns['four']->getDefault());
    }

    public function testCreateTableWithLimits()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('foo', 'string', array('limit' => 80));
        $table->end();

        $columns = array();
        foreach ($this->_conn->columns('testings') as $col) {
            $columns[$col->getName()] = $col;
        }
        $this->assertEquals(80, $columns['foo']->getLimit());
    }

    public function testAddColumnNotNullWithoutDefault()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('foo', 'string');
        $table->end();
        $this->_conn->addColumn('testings', 'bar', 'string', array('null' => false));

        try {
            $this->_conn->execute("INSERT INTO testings (foo, bar) VALUES ('hello', NULL)");
        } catch (Exception $e) { return; }
        $this->fail('Expected exception wasn\'t raised');

    }

    public function testAddColumnNotNullWithDefault()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('foo', 'string');
        $table->end();

        $this->_conn->execute("INSERT INTO testings (id, foo) VALUES ('1', 'hello')");

        $this->_conn->addColumn('testings', 'bar', 'string', array('null' => false, 'default' => 'default'));

        try {
            $this->_conn->execute("INSERT INTO testings (id, foo, bar) VALUES (2, 'hello', NULL)");
        } catch (Exception $e) { return; }
        $this->fail('Expected exception wasn\'t raised');
    }

    /**
     * We specifically do a manual INSERT here, and then test only the SELECT
     * functionality. This allows us to more easily catch INSERT being broken,
     * but SELECT actually working fine.
     */
    public function testNativeDecimalInsertManualVsAutomatic()
    {
        $correctValue = 12345678901234567890.0123456789;

        $this->_conn->delete('DELETE * FROM users');
        $this->_conn->addColumn("users", "wealth", 'decimal', array('precision' => 30, 'scale' => 10));

        // do a manual insertion
        $this->_conn->execute("INSERT INTO users (wealth) VALUES ('12345678901234567890.0123456789')");

        // SELECT @todo - type cast attribute values
        $user = (object)$this->_conn->selectOne('SELECT * FROM users');
        // assert_kind_of BigDecimal, row.wealth

        // If this assert fails, that means the SELECT is broken!
        $this->assertEquals($correctValue, $user->wealth);

        // Reset to old state
        $this->_conn->delete('DELETE * FROM users');

        // Now use the Rails insertion
        $this->_conn->insert('INSERT INTO users (wealth) VALUES (12345678901234567890.0123456789)');

        // SELECT @todo - type cast attribute values
        $user = (object)$this->_conn->selectOne('SELECT * FROM users');
        // assert_kind_of BigDecimal, row.wealth

        // If these asserts fail, that means the INSERT (create function, or cast to SQL) is broken!
        $this->assertEquals($correctValue, $user->wealth);
    }

    public function testNativeTypes()
    {
        $this->_conn->delete('DELETE * FROM users');

        $this->_conn->addColumn("users", "last_name",       'string');
        $this->_conn->addColumn("users", "bio",             'text');
        $this->_conn->addColumn("users", "age",             'integer');
        $this->_conn->addColumn("users", "height",          'float');
        $this->_conn->addColumn("users", "wealth",          'decimal', array('precision' => '30', 'scale' => '10'));
        $this->_conn->addColumn("users", "birthday",        'datetime');
        $this->_conn->addColumn("users", "favorite_day",    'date');
        $this->_conn->addColumn("users", "moment_of_truth", 'datetime');
        $this->_conn->addColumn("users", "male",            'boolean');

        $this->_conn->insert('INSERT INTO USERS (first_name, last_name, bio, age, height, wealth, birthday, favorite_day, moment_of_truth, male, company_id) ' .
                             "VALUES ('bob', 'bobsen', 'I was born ....', 18, 1.78, 12345678901234567890.0123456789, '2005-01-01 12:23:40', '1980-03-05', '1582-10-10 21:40:18', true, 1)");

        $bob = (object)$this->_conn->selectOne('SELECT * FROM users');
        $this->assertEquals('bob',             $bob->first_name);
        $this->assertEquals('bobsen',          $bob->last_name);
        $this->assertEquals('I was born ....', $bob->bio);
        $this->assertEquals(18,                $bob->age);

        // Test for 30 significent digits (beyond the 16 of float), 10 of them
        // after the decimal place.
        $this->assertEquals('12345678901234567890.0123456789', $bob->wealth);
        $this->assertEquals(true,                              $bob->male);

        // @todo - type casting
    }

    public function testUnabstractedDatabaseDependentTypes()
    {
        $this->_conn->delete('DELETE * FROM users');

        $this->_conn->addColumn('users', 'intelligence_quotient', 'tinyint');
        $this->_conn->insert('INSERT INTO users (intelligence_quotient) VALUES (300)');

        $jonnyg = (object)$this->_conn->selectOne('SELECT * FROM users');
        $this->assertEquals('127', $jonnyg->intelligence_quotient);
    }

    public function testAddRemoveSingleField()
    {
        $this->assertFalse(in_array('last_name', $this->_columnNames('users')));

        $this->_conn->addColumn('users', 'last_name', 'string');
        $this->assertTrue(in_array('last_name', $this->_columnNames('users')));

        $this->_conn->removeColumn('users', 'last_name');
        $this->assertFalse(in_array('last_name', $this->_columnNames('users')));
    }

    public function testAddRename()
    {
        $this->_conn->delete('DELETE * FROM users');

        $this->_conn->addColumn('users', 'girlfriend', 'string');
        $this->_conn->insert("INSERT INTO users (girlfriend) VALUES ('bobette')");

        $this->_conn->renameColumn('users', 'girlfriend', 'exgirlfriend');

        $bob = (object)$this->_conn->selectOne('SELECT * FROM users');
        $this->assertEquals('bobette', $bob->exgirlfriend);
    }

    public function testRenameColumn()
    {
        $this->_conn->renameColumn('users', 'first_name', 'nick_name');
        $this->assertTrue(in_array('nick_name', $this->_columnNames('users')));
    }

    public function testRenameColumnWithSqlReservedWord()
    {
        $this->_conn->renameColumn('users', 'first_name', 'group');
        $this->assertTrue(in_array('group', $this->_columnNames('users')));
    }

    public function testRenameTable()
    {
        $table = $this->_conn->createTable('octopuses');
            $table->column('url', 'string');
        $table->end();

        $this->_conn->renameTable('octopuses', 'octopi');

        $sql = "INSERT INTO octopi (id, url) VALUES (1, 'http://www.foreverflying.com/octopus-black7.jpg')";
        $this->_conn->execute($sql);

        $this->assertEquals('http://www.foreverflying.com/octopus-black7.jpg',
                $this->_conn->selectValue("SELECT url FROM octopi WHERE id=1"));
    }

    public function testRenameTableWithAnIndex()
    {
        $table = $this->_conn->createTable('octopuses');
            $table->column('url', 'string');
        $table->end();
        $this->_conn->addIndex('octopuses', 'url');
        $this->_conn->renameTable('octopuses', 'octopi');

        $sql = "INSERT INTO octopi (id, url) VALUES (1, 'http://www.foreverflying.com/octopus-black7.jpg')";
        $this->_conn->execute($sql);

        $this->assertEquals('http://www.foreverflying.com/octopus-black7.jpg',
                $this->_conn->selectValue("SELECT url FROM octopi WHERE id=1"));

        $indexes = $this->_conn->indexes('octopi');
        $this->assertEquals('url', $indexes[0]->columns[0]);
    }

    public function testChangeColumn()
    {
        $this->_conn->addColumn('users', 'age', 'integer');
        $oldColumns = $this->_conn->columns('users', "User Columns");

        $found = false;
        foreach ($oldColumns as $c) {
            if ($c->getName() == 'age' && $c->getType() == 'integer') { $found = true; }
        }
        $this->assertTrue($found);

        $this->_conn->changeColumn('users', 'age', 'string');

        $newColumns = $this->_conn->columns('users', "User Columns");

        $found = false;
        foreach ($newColumns as $c) {
            if ($c->getName() == 'age' && $c->getType() == 'integer') { $found = true; }
        }
        $this->assertFalse($found);
        $found = false;
        foreach ($newColumns as $c) {
            if ($c->getName() == 'age' && $c->getType() == 'string') { $found = true; }
        }
        $this->assertTrue($found);

        $found = false;
        foreach ($oldColumns as $c) {
            if ($c->getName() == 'approved' && $c->getType() == 'boolean' &&
                $c->getDefault() == true) { $found = true; }
        }
        $this->assertTrue($found);

        // changeColumn() throws exception on error
        $this->_conn->changeColumn('users', 'approved', 'boolean', array('default' => false));

        $newColumns = $this->_conn->columns('users', "User Columns");

        $found = false;
        foreach ($newColumns as $c) {
            if ($c->getName() == 'approved' && $c->getType() == 'boolean' &&
                $c->getDefault() == true) { $found = true; }
        }
        $this->assertFalse($found);

        $found = false;
        foreach ($newColumns as $c) {
            if ($c->getName() == 'approved' && $c->getType() == 'boolean' &&
                $c->getDefault() == false) { $found = true; }
        }
        $this->assertTrue($found);

        // changeColumn() throws exception on error
        $this->_conn->changeColumn('users', 'approved', 'boolean', array('default' => true));
    }

    public function testChangeColumnWithNilDefault()
    {
        $this->markTestSkipped();
        $this->_conn->addColumn('users', 'contributor', 'boolean', array('default' => true));
        $user = new User;
        $this->assertTrue($user->contributor);

        // changeColumn() throws exception on error
        $this->_conn->changeColumn('users', 'contributor', 'boolean', array('default' => null));

        $user = new User;
        $this->assertNull($user->contributor);
    }

    public function testChangeColumnWithNewDefault()
    {
        $this->markTestSkipped();
        $this->_conn->addColumn('users', 'administrator', 'boolean', array('default' => true));
        $user = new User;
        $this->assertTrue($user->administrator);

        // changeColumn() throws exception on error
        $this->_conn->changeColumn('users', 'administrator', 'boolean', array('default' => false));

        $user = new User;
        $this->assertFalse($user->administrator);
    }

    public function testChangeColumnDefault()
    {
        $this->markTestSkipped();
        $this->_conn->changeColumnDefault('users', 'first_name', 'Tester');

        $user = new User;
        $this->assertEquals('Tester', $user->first_name);
    }

    public function testChangeColumnDefaultToNull()
    {
        $this->markTestSkipped();
        $this->_conn->changeColumnDefault('users', 'first_name', null);

        $user = new User;
        $this->assertNull($user->first_name);
    }

    public function testAddTable()
    {
        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);

        $m = new WeNeedReminders1;
        $m->up();

        $this->_conn->insert("INSERT INTO reminders (content, remind_at) VALUES ('hello world', '2005-01-01 11:10:01')");

        $reminder = (object)$this->_conn->selectOne('SELECT * FROM reminders');
        $this->assertEquals('hello world', $reminder->content);

        $m->down();
        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);
    }

    public function testAddTableWithDecimals()
    {
        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM big_numbers");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);

        $m = new GiveMeBigNumbers;
        $m->up();

        $this->_conn->insert('INSERT INTO big_numbers (bank_balance, big_bank_balance, world_population, my_house_population, value_of_e) VALUES (1586.43, 1000234000567.95, 6000000000, 3, 2.7182818284590452353602875)');

        $b = (object)$this->_conn->selectOne('SELECT * FROM big_numbers');
        $this->assertNotNull($b->bank_balance);
        $this->assertNotNull($b->big_bank_balance);
        $this->assertNotNull($b->world_population);
        $this->assertNotNull($b->my_house_population);
        $this->assertNotNull($b->value_of_e);

        $m->down();
        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM big_numbers");
        } catch (Exception $e) {}
        $this->assertType('Horde_Db_Exception', $e);
    }

    public function testCreateTableWithBinaryColumn()
    {
        try {
            $table = $this->_conn->createTable('binary_testings');
                $table->column('data', 'binary', array('null' => false));
            $table->end();
        } catch (Exception $e) { $this->fail('Unexepected exception raised'); }

        $columns = $this->_conn->columns('binary_testings');

        foreach ($columns as $c) {
            if ($c->getName() == 'data') { $dataColumn = $c; }
        }
        $this->assertEquals('', $dataColumn->getDefault());
    }


    protected function _columnNames($tableName)
    {
        $columns = array();
        foreach ($this->_conn->columns($tableName) as $c) {
            $columns[] = $c->name;
        }
        return $columns;
    }

}
