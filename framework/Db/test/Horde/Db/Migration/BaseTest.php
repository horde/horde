<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
        try {
            $this->_conn = new Horde_Db_Adapter_Pdo_Sqlite(array(
                'dbname' => ':memory:',
            ));
        } catch (Horde_Db_Exception $e) {
            $this->markTestSkipped('The sqlite adapter is not available');
        }

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
    }

    public function testChangeColumnWithNilDefault()
    {
        $this->_conn->addColumn('users', 'contributor', 'boolean', array('default' => true));
        $users = $this->_conn->table('users');
        $this->assertTrue($users->contributor->getDefault());

        // changeColumn() throws exception on error
        $this->_conn->changeColumn('users', 'contributor', 'boolean', array('default' => null));

        $users = $this->_conn->table('users');
        $this->assertNull($users->contributor->getDefault());
    }

    public function testChangeColumnWithNewDefault()
    {
        $this->_conn->addColumn('users', 'administrator', 'boolean', array('default' => true));
        $users = $this->_conn->table('users');
        $this->assertTrue($users->administrator->getDefault());

        // changeColumn() throws exception on error
        $this->_conn->changeColumn('users', 'administrator', 'boolean', array('default' => false));

        $users = $this->_conn->table('users');
        $this->assertFalse($users->administrator->getDefault());
    }

    public function testChangeColumnDefault()
    {
        $this->_conn->changeColumnDefault('users', 'first_name', 'Tester');

        $users = $this->_conn->table('users');
        $this->assertEquals('Tester', $users->first_name->getDefault());
    }

    public function testChangeColumnDefaultToNull()
    {
        $this->_conn->changeColumnDefault('users', 'first_name', null);

        $users = $this->_conn->table('users');
        $this->assertNull($users->first_name->getDefault());
    }

    public function testAddTable()
    {
        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertInstanceOf('Horde_Db_Exception', $e);

        $m = new WeNeedReminders1($this->_conn);
        $m->up();

        $this->_conn->insert("INSERT INTO reminders (content, remind_at) VALUES ('hello world', '2005-01-01 11:10:01')");

        $reminder = (object)$this->_conn->selectOne('SELECT * FROM reminders');
        $this->assertEquals('hello world', $reminder->content);

        $m->down();
        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM reminders");
        } catch (Exception $e) {}
        $this->assertInstanceOf('Horde_Db_Exception', $e);
    }

    public function testAddTableWithDecimals()
    {
        $e = null;
        try {
            $this->_conn->selectValues("SELECT * FROM big_numbers");
        } catch (Exception $e) {}
        $this->assertInstanceOf('Horde_Db_Exception', $e);

        $m = new GiveMeBigNumbers($this->_conn);
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
        $this->assertInstanceOf('Horde_Db_Exception', $e);
    }
}
