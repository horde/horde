<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @group      horde_db
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */
class Horde_Db_Adapter_Pdo_PgsqlTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        list($this->_conn, $this->_cache) = Horde_Db_AllTests::$connFactory->getConnection();

        // clear out detritus from any previous test runs.
        $this->_dropTestTables();

        $table = $this->_conn->createTable('unit_tests');
          $table->column('integer_value',   'integer',  array('limit' => 11, 'default' => 0));
          $table->column('string_value',    'string',   array('limit' => 255, 'default' => ''));
          $table->column('text_value',      'text',     array('null' => false, 'default' => ''));
          $table->column('float_value',     'float',    array('precision' => 2, 'scale' => 1, 'default' => 0.0));
          $table->column('decimal_value',   'decimal',  array('precision' => 2, 'scale' => 1, 'default' => 0.0));
          $table->column('datetime_value',  'datetime', array());
          $table->column('date_value',      'date',     array());
          $table->column('time_value',      'time',     array('default' => '00:00:00'));
          $table->column('blob_value',      'binary',   array('null' => false, 'default' => ''));
          $table->column('boolean_value',   'boolean',  array('default' => false));
          $table->column('email_value',     'string',   array('limit' => 255, 'default' => ''));
        $table->end();
        $this->_conn->addIndex('unit_tests', 'string_value', array('name' => 'string_value'));
        $this->_conn->addIndex('unit_tests', 'integer_value', array('name' => 'integer_value', 'unique' => true));
        $this->_conn->addIndex('unit_tests', array('integer_value', 'string_value'), array('name' => 'integer_string'));

        // read sql file for statements
        $statements = array();
        $current_stmt = '';
        $fp = fopen(dirname(__FILE__) . '/../../fixtures/unit_tests.sql', 'r');
        while ($line = fgets($fp, 8192)) {
            $line = rtrim(preg_replace('/^(.*)--.*$/s', '\1', $line));
            if (!$line) {
                continue;
            }

            $current_stmt .= $line;

            if (substr($line, -1) == ';') {
                // leave off the ending ;
                $statements[] = substr($current_stmt, 0, -1);
                $current_stmt = '';
            }
        }

        // run statements
        foreach ($statements as $stmt) {
            $this->_conn->execute($stmt);
        }
    }

    protected function tearDown()
    {
        // clean up
        $this->_dropTestTables();
    }


    /*##########################################################################
    # Connection
    ##########################################################################*/

    public function testConnect()
    {
        $this->assertTrue($this->_conn->isActive());
    }

    public function testDisconnect()
    {
        $this->_conn->disconnect();
        $this->assertFalse($this->_conn->isActive());

        $this->_conn->connect();
        $this->assertTrue($this->_conn->isActive());
    }

    public function testReconnect()
    {
        $this->_conn->reconnect();
        $this->assertTrue($this->_conn->isActive());
    }


    /*##########################################################################
    # Accessor
    ##########################################################################*/

    public function testAdapterName()
    {
        $this->assertEquals('PDO_PostgreSQL', $this->_conn->adapterName());
    }

    public function testSupportsMigrations()
    {
        $this->assertTrue($this->_conn->supportsMigrations());
    }

    public function testSupportsCountDistinct()
    {
        $this->assertTrue($this->_conn->supportsCountDistinct());
    }


    /*##########################################################################
    # Database Statements
    ##########################################################################*/

    public function testExecute()
    {
        $sql = "SELECT * FROM unit_tests WHERE id='1'";
        $result = $this->_conn->execute($sql);
        $row = $result->fetch();
        $this->assertEquals(1, $row['id']);
    }

    public function testSelect()
    {
        $sql = "SELECT * FROM unit_tests WHERE id='1'";
        $result = $this->_conn->select($sql);
        $this->assertInstanceOf('Traversable', $result);
        $this->assertGreaterThan(0, count($result));

        foreach ($result as $row) break;
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['id']);
    }

    public function testSelectWithBoundParameters()
    {
        $sql = "SELECT * FROM unit_tests WHERE id=?";
        $result = $this->_conn->select($sql, array(1));
        $this->assertInstanceOf('Traversable', $result);
        $this->assertGreaterThan(0, count($result));

        foreach ($result as $row) break;
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['id']);
    }

    public function testSelectWithBoundParametersQuotesString()
    {
        $sql = "SELECT * FROM unit_tests WHERE string_value=?";
        $result = $this->_conn->select($sql, array('name a'));
        $this->assertInstanceOf('Traversable', $result);
        $this->assertGreaterThan(0, count($result));

        foreach ($result as $row) break;
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['id']);
    }

    public function testSelectAll()
    {
        $sql = "SELECT * FROM unit_tests WHERE id='1'";
        $result = $this->_conn->selectAll($sql);
        $this->assertInternalType('array', $result);
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals(1, $result[0]['id']);
    }

    public function testSelectOne()
    {
        $sql = "SELECT * FROM unit_tests WHERE id='1'";
        $result = $this->_conn->selectOne($sql);
        $this->assertEquals(1, $result['id']);
    }

    public function testSelectValue()
    {
        $sql = "SELECT * FROM unit_tests WHERE id='1'";
        $result = $this->_conn->selectValue($sql);
        $this->assertEquals(1, $result);
    }

    public function testSelectValues()
    {
        $sql = "SELECT * FROM unit_tests";
        $result = $this->_conn->selectValues($sql);
        $this->assertEquals(array(1, 2, 3, 4, 5, 6), $result);
    }

    public function testInsert()
    {
        $sql = "INSERT INTO unit_tests (id, integer_value) VALUES (7, 999)";
        $result = $this->_conn->insert($sql, null, null, null, 7);

        $this->assertEquals(7, $result);
    }

    public function testUpdate()
    {
        $sql = "UPDATE unit_tests SET integer_value=999 WHERE id IN (1)";
        $result = $this->_conn->update($sql);

        $this->assertEquals(1, $result);
    }

    public function testDelete()
    {
        $sql = "DELETE FROM unit_tests WHERE id IN (1,2)";
        $result = $this->_conn->delete($sql);

        $this->assertEquals(2, $result);
    }

    public function testTransactionStarted()
    {
        $this->assertFalse($this->_conn->transactionStarted());
        $this->_conn->beginDbTransaction();

        $this->assertTrue($this->_conn->transactionStarted());
        $this->_conn->commitDbTransaction();

        $this->assertFalse($this->_conn->transactionStarted());
    }

    public function testTransactionCommit()
    {
        $this->_conn->beginDbTransaction();
        $sql = "INSERT INTO unit_tests (id, integer_value) VALUES (7, 999)";
        $this->_conn->insert($sql, null, null, 'id', 7);
        $this->_conn->commitDbTransaction();

        // make sure it inserted
        $sql = "SELECT integer_value FROM unit_tests WHERE id='7'";
        $this->assertEquals('999', $this->_conn->selectValue($sql));
    }

    public function testTransactionRollback()
    {
        $this->_conn->beginDbTransaction();
        $sql = "INSERT INTO unit_tests (id, integer_value) VALUES (7, 999)";
        $this->_conn->insert($sql, null, null, 'id', 7);
        $this->_conn->rollbackDbTransaction();

        // make sure it not inserted
        $sql = "SELECT integer_value FROM unit_tests WHERE id='7'";
        $this->assertEquals(null, $this->_conn->selectValue($sql));
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    public function testQuoteNull()
    {
        $this->assertEquals('NULL', $this->_conn->quote(null));
    }

    public function testQuoteTrue()
    {
        $this->assertEquals("'t'", $this->_conn->quote(true));
    }

    public function testQuoteFalse()
    {
        $this->assertEquals("'f'", $this->_conn->quote(false));
    }

    public function testQuoteInteger()
    {
        $this->assertEquals('42', $this->_conn->quote(42));
    }

    public function testQuoteFloat()
    {
        $this->assertEquals('42.2', $this->_conn->quote(42.2));
        setlocale(LC_NUMERIC, 'de_DE.UTF-8');
        $this->assertEquals('42.2', $this->_conn->quote(42.2));
    }

    public function testQuoteString()
    {
        $this->assertEquals("'my string'", $this->_conn->quote('my string'));
    }

    public function testQuoteDirtyString()
    {
        $this->assertEquals("'derek''s string'", $this->_conn->quote('derek\'s string'));
    }

    public function testQuoteColumnName()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'int(11)');
        $this->assertEquals('1', $this->_conn->quote(true, $col));
    }

    public function testQuoteBinary()
    {
        // Test string is foo\0bar\baz'boo\'bee - should be 20 bytes long
        $original = base64_decode('Zm9vAGJhclxiYXonYm9vXCdiZWU=');

        $table = $this->_conn->createTable('binary_testings');
            $table->column('data', 'binary', array('null' => false));
        $table->end();

        $this->_conn->insert('INSERT INTO binary_testings (data) VALUES (?)', array(new Horde_Db_Value_Binary($original)));
        $retrieved = $this->_conn->selectValue('SELECT data FROM binary_testings');

        $columns = $this->_conn->columns('binary_testings');
        $retrieved = $columns['data']->binaryToString($retrieved);

        $this->assertEquals($original, $retrieved);
    }


    /*##########################################################################
    # Schema Statements
    ##########################################################################*/

    public function testNativeDatabaseTypes()
    {
        $types = $this->_conn->nativeDatabaseTypes();
        $this->assertEquals(array('name' => 'integer', 'limit' => null), $types['integer']);
    }

    public function testTableAliasLength()
    {
        $len = $this->_conn->tableAliasLength();
        $this->assertGreaterThanOrEqual(63, $len);
    }

    public function testTableAliasFor()
    {
        $alias = $this->_conn->tableAliasFor('my_table_name');
        $this->assertEquals('my_table_name', $alias);
    }

    public function testSchemaSearchPath()
    {
        $schemaSearchPath = $this->_conn->getSchemaSearchPath();
        $this->assertGreaterThan(0, strlen($schemaSearchPath));
    }

    public function testTables()
    {
        $tables = $this->_conn->tables();
        $this->assertTrue(count($tables) > 0);
        $this->assertContains('unit_tests', $tables);
    }

    public function testPrimaryKey()
    {
        $pk = $this->_conn->primaryKey('unit_tests');
        $this->assertEquals('id', (string)$pk);
        $this->assertEquals(1, count($pk->columns));
        $this->assertEquals('id', $pk->columns[0]);

        $table = $this->_conn->createTable('pk_tests', array('autoincrementKey' => false));
        $table->column('foo', 'string');
        $table->column('bar', 'string');
        $table->end();
        $pk = $this->_conn->primaryKey('pk_tests');
        $this->assertEmpty((string)$pk);
        $this->assertEquals(0, count($pk->columns));
        $this->_conn->addPrimaryKey('pk_tests', 'foo');
        $pk = $this->_conn->primaryKey('pk_tests');
        $this->assertEquals('foo', (string)$pk);
        $this->assertEquals(1, count($pk->columns));
        $this->_conn->removePrimaryKey('pk_tests');
        $pk = $this->_conn->primaryKey('pk_tests');
        $this->assertEmpty((string)$pk);
        $this->assertEquals(0, count($pk->columns));
        $this->_conn->addPrimaryKey('pk_tests', array('foo', 'bar'));
        $pk = $this->_conn->primaryKey('pk_tests');
        $this->assertEquals('foo,bar', (string)$pk);
    }

    public function testIndexes()
    {
        $indexes = $this->_conn->indexes('unit_tests');
        $this->assertEquals(3, count($indexes));

        // sort by name so we can predict the order of indexes
        usort($indexes, create_function('$a, $b', 'return strcmp($a->name, $b->name);'));

        // multi-column index
        $col = array('integer_value', 'string_value');
        $this->assertEquals('unit_tests',     $indexes[0]->table);
        $this->assertEquals('integer_string', $indexes[0]->name);
        $this->assertEquals(false,            $indexes[0]->unique);
        $this->assertEquals($col,             $indexes[0]->columns);

        // unique index
        $col = array('integer_value');
        $this->assertEquals('unit_tests',    $indexes[1]->table);
        $this->assertEquals('integer_value', $indexes[1]->name);
        $this->assertEquals(true,            $indexes[1]->unique);
        $this->assertEquals($col,            $indexes[1]->columns);

        // normal index
        $col = array('string_value');
        $this->assertEquals('unit_tests',   $indexes[2]->table);
        $this->assertEquals('string_value', $indexes[2]->name);
        $this->assertEquals(false,          $indexes[2]->unique);
        $this->assertEquals($col,           $indexes[2]->columns);
    }

    public function testColumns()
    {
        $columns = $this->_conn->columns('unit_tests');
        $this->assertEquals(12, count($columns));

        $col = $columns['id'];
        $this->assertEquals('id',      $col->getName());
        $this->assertEquals('integer', $col->getType());
        $this->assertEquals(false,     $col->isNull());
        $this->assertEquals(null,      $col->getLimit());
        $this->assertEquals('',        $col->getDefault());
        $this->assertEquals('integer', $col->getSqlType());
        $this->assertEquals(false,     $col->isText());
        $this->assertEquals(true,      $col->isNumber());
    }

    public function testCreateTable()
    {
        $this->_createTestTable('sports');

        $sql = "SELECT id FROM sports WHERE id = 1";
        $this->assertEquals(1, $this->_conn->selectValue($sql));
    }

    public function testCreateTableNoPk()
    {
        $this->_createTestTable('sports', array('autoincrementKey' => false));

        try {
            $sql = "SELECT id FROM sports WHERE id = 1";
            $this->assertNull($this->_conn->selectValue($sql));
        } catch (Exception $e) {
            return;
        }
        $this->fail("Expected exception for no pk");
    }

    public function testCreateTableWithNamedPk()
    {
        $this->_createTestTable('sports', array('autoincrementKey' => 'sports_id'));

        $sql = "SELECT sports_id FROM sports WHERE sports_id = 1";
        $this->assertEquals(1, $this->_conn->selectValue($sql));

        try {
            $sql = "SELECT id FROM sports WHERE id = 1";
            $this->assertNull($this->_conn->selectValue($sql));
        } catch (Exception $e) {
            return;
        }
        $this->fail("Expected exception for wrong pk name");
    }

    public function testCreateTableWithSeparatePk()
    {
        $table = $this->_conn->createTable('testings', array('autoincrementKey' => false));
          $table->column('foo', 'autoincrementKey');
          $table->column('bar', 'integer');
        $table->end();

        $pkColumn = $table['foo'];
        $this->assertEquals('"foo" serial primary key', $pkColumn->toSql());

        $this->_conn->insert("INSERT INTO testings (bar) VALUES (1)");

        $sql = "SELECT * FROM testings WHERE foo = 1";
        $result = $this->_conn->select($sql);
        $this->assertEquals(1, count($result));

        // This should fail.
        try {
            $this->_conn->insert("INSERT INTO testings (foo) VALUES (NULL)");
            $this->fail("Expected exception for inserting null value");
        } catch (Exception $e) {}

        // Manually insert a primary key value.
        $this->_conn->insert("INSERT INTO testings (foo, bar) VALUES (2, 1)");
        $this->_conn->insert("INSERT INTO testings (bar) VALUES (1)");
    }

    public function testAlterTableWithSeparatePk()
    {
        $table = $this->_conn->createTable('testings', array('autoincrementKey' => false));
          $table->column('foo', 'integer');
          $table->column('bar', 'integer');
        $table->end();

        // Convert 'foo' to auto-increment
        $this->_conn->changeColumn('testings', 'foo', 'autoincrementKey');

        $this->_conn->insert("INSERT INTO testings (bar) VALUES (1)");

        $sql = "SELECT * FROM testings WHERE foo = 1";
        $result = $this->_conn->select($sql);
        $this->assertEquals(1, count($result));
    }

    public function testCreateTableCompositePk()
    {
        $table = $this->_conn->createTable('testings', array('autoincrementKey' => array('a_id', 'b_id')));
          $table->column('a_id', 'integer');
          $table->column('b_id', 'integer');
        $table->end();

        $pk = $this->_conn->primaryKey('testings');
        $this->assertEquals(array('a_id', 'b_id'), $pk->columns);
    }

    public function testCreateTableForce()
    {
        $this->_createTestTable('sports');
        $this->_createTestTable('sports', array('force' => true));

        $sql = "SELECT id FROM sports WHERE id = 1";
        $this->assertEquals(1, $this->_conn->selectValue($sql));
    }

    public function testCreateTableTemporary()
    {
        $this->_createTestTable('sports', array('temporary' => true));

        $sql = "SELECT id FROM sports WHERE id = 1";
        $this->assertEquals(1, $this->_conn->selectValue($sql));
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
            $this->_conn->insert("INSERT INTO testings (foo) VALUES (NULL)");
        } catch (Exception $e) { return; }
        $this->fail('Expected exception wasn\'t raised');
    }

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
        $this->assertEquals(1, $columns['four']->getDefault());
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

    public function testRenameTable()
    {
        // Simple rename then select test
        $this->_createTestTable('sports');
        $this->_conn->renameTable('sports', 'my_sports');

        $sql = "SELECT id FROM my_sports WHERE id = 1";
        $this->assertEquals("1", $this->_conn->selectValue($sql));

        // Make sure the old table name isn't still there
        try {
            $sql = "SELECT id FROM sports WHERE id = 1";
            $this->_conn->execute($sql);
        } catch (Exception $e) {
            return;
        }
        $this->fail("Table exists where it shouldn't have");

        // Rename then insert test
        $table = $this->_conn->createTable('octopuses');
            $table->column('url', 'string');
        $table->end();

        $this->_conn->renameTable('octopuses', 'octopi');

        $sql = "INSERT INTO octopi (id, url) VALUES (1, 'http://www.foreverflying.com/octopus-black7.jpg')";
        $this->_conn->insert($sql);

        $this->assertEquals('http://www.foreverflying.com/octopus-black7.jpg',
                $this->_conn->selectValue("SELECT url FROM octopi WHERE id=1"));

        // Make sure the old table name isn't still there
        try {
            $sql = "SELECT id FROM octopuses WHERE id = 1";
            $this->_conn->execute($sql);
        } catch (Exception $e) {
            return;
        }
        $this->fail("Table exists where it shouldn't have");
    }

    public function testRenameTableWithAnIndex()
    {
        $table = $this->_conn->createTable('octopuses');
            $table->column('url', 'string');
        $table->end();
        $this->_conn->addIndex('octopuses', 'url');
        $this->_conn->renameTable('octopuses', 'octopi');

        $sql = "INSERT INTO octopi (id, url) VALUES (1, 'http://www.foreverflying.com/octopus-black7.jpg')";
        $this->_conn->insert($sql, null, null, 'id', 1);

        $this->assertEquals('http://www.foreverflying.com/octopus-black7.jpg',
                $this->_conn->selectValue("SELECT url FROM octopi WHERE id=1"));

        $indexes = $this->_conn->indexes('octopi');
        $this->assertEquals('url', $indexes[0]->columns[0]);
    }

    public function testDropTable()
    {
        $this->_createTestTable('sports');
        $this->_conn->dropTable('sports');

        try {
            $sql = "SELECT id FROM sports WHERE id = 1";
            $this->_conn->execute($sql);
        } catch (Exception $e) {
            return;
        }
        $this->fail("Table exists where it shouldn't have");
    }

    public function testAddColumn()
    {
        $this->_createTestTable('sports');
        $this->_conn->addColumn('sports', 'modified_at', 'date');
        $this->_conn->update("UPDATE sports SET modified_at = '2007-01-01'");

        $sql = "SELECT modified_at FROM sports WHERE id = 1";
        $this->assertEquals("2007-01-01", $this->_conn->selectValue($sql));
    }

    public function testRemoveColumn()
    {
        $this->_createTestTable('sports');
        $sql = "SELECT name FROM sports WHERE id = 1";
        $this->assertEquals("mlb", $this->_conn->selectValue($sql));

        $this->_conn->removeColumn('sports', 'name');

        try {
            $sql = "SELECT name FROM sports WHERE id = 1";
            $this->_conn->execute($sql);
        } catch (Exception $e) {
            return;
        }
        $this->fail("Column exists where it shouldn't have");
    }

    public function testChangeColumn()
    {
        $this->_createTestUsersTable();

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

    public function testChangeColumnDefault()
    {
        $this->_createTestTable('sports');
        $beforeChange = $this->_getColumn('sports', 'name');
        $this->assertEquals('', $beforeChange->getDefault());

        $this->_conn->changeColumnDefault('sports', 'name', 'test');

        $afterChange = $this->_getColumn('sports', 'name');
        $this->assertEquals('test', $afterChange->getDefault());
    }

    public function testChangeColumnType()
    {
        $this->_createTestTable('sports');
        $beforeChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('boolean', $beforeChange->getSqlType());

        $this->_conn->changeColumn('sports', 'is_college', 'string');

        $afterChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('character varying(255)', $afterChange->getSqlType());

        $table = $this->_conn->createTable('text_to_binary');
        $table->column('data', 'text');
        $table->end();
        $this->_conn->insert('INSERT INTO text_to_binary (data) VALUES (?)',
                             array("foobar"));

        $this->_conn->changeColumn('text_to_binary', 'data', 'binary');

        $afterChange = $this->_getColumn('text_to_binary', 'data');
        $this->assertEquals('bytea', $afterChange->getSqlType());
        $this->assertEquals(
            "foobar",
            stream_get_contents($this->_conn->selectValue('SELECT data FROM text_to_binary')));
    }

    public function testChangeColumnLimit()
    {
        $this->_createTestTable('sports');
        $beforeChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('boolean', $beforeChange->getSqlType());

        $this->_conn->changeColumn('sports', 'is_college', 'string',
                                   array('limit' => '40'));

        $afterChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('character varying(40)', $afterChange->getSqlType());
    }

    public function testChangeColumnPrecisionScale()
    {
        $this->_createTestTable('sports');
        $beforeChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('boolean', $beforeChange->getSqlType());

        $this->_conn->changeColumn('sports', 'is_college', 'decimal',
                                   array('precision' => '5', 'scale' => '2'));

        $afterChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('numeric(5,2)', $afterChange->getSqlType());
    }

    public function testRenameColumn()
    {
        $this->_createTestUsersTable();

        $this->_conn->renameColumn('users', 'first_name', 'nick_name');
        $this->assertTrue(in_array('nick_name', $this->_columnNames('users')));

        $this->_createTestTable('sports');

        $beforeChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('boolean', $beforeChange->getSqlType());

        $this->_conn->renameColumn('sports', 'is_college', 'is_renamed');

        $afterChange = $this->_getColumn('sports', 'is_renamed');
        $this->assertEquals('boolean', $afterChange->getSqlType());
    }

    public function testRenameColumnWithSqlReservedWord()
    {
        $this->_createTestUsersTable();

        $this->_conn->renameColumn('users', 'first_name', 'group');
        $this->assertTrue(in_array('group', $this->_columnNames('users')));
    }

    public function testAddIndex()
    {
        $this->_createTestUsersTable();

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

    public function testAddIndexDefault()
    {
        $this->_createTestTable('sports');
        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNull($index);

        $this->_conn->addIndex('sports', 'is_college');

        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNotNull($index);
    }

    public function testAddIndexMultiColumn()
    {
        $this->_createTestTable('sports');
        $index = $this->_getIndex('sports', array('name', 'is_college'));
        $this->assertNull($index);

        $this->_conn->addIndex('sports', array('name', 'is_college'));

        $index = $this->_getIndex('sports', array('name', 'is_college'));
        $this->assertNotNull($index);
    }

    public function testAddIndexUnique()
    {
        $this->_createTestTable('sports');
        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNull($index);

        $this->_conn->addIndex('sports', 'is_college', array('unique' => true));

        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNotNull($index);
        $this->assertTrue($index->unique);
    }

    public function testAddIndexName()
    {
        $this->_createTestTable('sports');
        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNull($index);

        $this->_conn->addIndex('sports', 'is_college', array('name' => 'sports_test'));

        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNotNull($index);
        $this->assertEquals('sports_test', $index->name);
    }

    public function testRemoveIndexSingleColumn()
    {
        $this->_createTestTable('sports');

        // add the index
        $this->_conn->addIndex('sports', 'is_college');
        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNotNull($index);

        // remove it again
        $this->_conn->removeIndex('sports', array('column' => 'is_college'));
        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNull($index);
    }

    public function testRemoveIndexMultiColumn()
    {
        $this->_createTestTable('sports');

        // add the index
        $this->_conn->addIndex('sports', array('name', 'is_college'));
        $index = $this->_getIndex('sports', array('name', 'is_college'));
        $this->assertNotNull($index);

        // remove it again
        $this->_conn->removeIndex('sports', array('column' => array('name', 'is_college')));
        $index = $this->_getIndex('sports', array('name', 'is_college'));
        $this->assertNull($index);
    }

    public function testRemoveIndexByName()
    {
        $this->_createTestTable('sports');

        // add the index
        $this->_conn->addIndex('sports', 'is_college', array('name' => 'sports_test'));
        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNotNull($index);

        // remove it again
        $this->_conn->removeIndex('sports', array('name' => 'sports_test'));
        $index = $this->_getIndex('sports', 'is_college');
        $this->assertNull($index);
    }

    public function testIndexNameInvalid()
    {
        try {
            $name = $this->_conn->indexName('sports');
        } catch (Horde_Db_Exception $e) {
            return;
        }
        $this->fail("Adding an index with crappy options worked where it shouldn't have");
    }

    public function testIndexNameBySingleColumn()
    {
        $name = $this->_conn->indexName('sports', array('column' => 'is_college'));
        $this->assertEquals('index_sports_on_is_college', $name);
    }

    public function testIndexNameByMultiColumn()
    {
        $name = $this->_conn->indexName('sports', array('column' =>
                                                array('name', 'is_college')));
        $this->assertEquals('index_sports_on_name_and_is_college', $name);
    }

    public function testIndexNameByName()
    {
        $name = $this->_conn->indexName('sports', array('name' => 'test'));
        $this->assertEquals('test', $name);
    }

    public function testTypeToSqlTypePrimaryKey()
    {
        $result = $this->_conn->typeToSql('autoincrementKey');
        $this->assertEquals('serial primary key', $result);
    }

    public function testTypeToSqlTypeString()
    {
        $result = $this->_conn->typeToSql('string');
        $this->assertEquals('character varying(255)', $result);
    }

    public function testTypeToSqlTypeText()
    {
        $result = $this->_conn->typeToSql('text');
        $this->assertEquals('text', $result);
    }

    public function testTypeToSqlTypeBinary()
    {
        $result = $this->_conn->typeToSql('binary');
        $this->assertEquals('bytea', $result);
    }

    public function testTypeToSqlTypeFloat()
    {
        $result = $this->_conn->typeToSql('float');
        $this->assertEquals('float', $result);
    }

    public function testTypeToSqlTypeDatetime()
    {
        $result = $this->_conn->typeToSql('datetime');
        $this->assertEquals('timestamp', $result);
    }

    public function testTypeToSqlTypeTimestamp()
    {
        $result = $this->_conn->typeToSql('timestamp');
        $this->assertEquals('timestamp', $result);
    }

    public function testTypeToSqlInt()
    {
        $result = $this->_conn->typeToSql('integer');
        $this->assertEquals('integer', $result);
    }

    public function testTypeToSqlIntLimit()
    {
        $result = $this->_conn->typeToSql('integer', '1');
        $this->assertEquals('smallint', $result);
    }

    public function testTypeToSqlDecimalPrecision()
    {
        $result = $this->_conn->typeToSql('decimal', null, '5');
        $this->assertEquals('decimal(5)', $result);
    }

    public function testTypeToSqlDecimalScale()
    {
        $result = $this->_conn->typeToSql('decimal', null, '5', '2');
        $this->assertEquals('decimal(5, 2)', $result);
    }

    public function testTypeToSqlBoolean()
    {
        $result = $this->_conn->typeToSql('boolean');
        $this->assertEquals('boolean', $result);
    }

    public function testAddColumnOptions()
    {
        $result = $this->_conn->addColumnOptions("test", array());
        $this->assertEquals("test", $result);
    }

    public function testAddColumnOptionsDefault()
    {
        $options = array('default' => '0');
        $result = $this->_conn->addColumnOptions("test", $options);
        $this->assertEquals("test DEFAULT '0'", $result);
    }

    public function testAddColumnOptionsNull()
    {
        $options = array('null' => true);
        $result = $this->_conn->addColumnOptions("test", $options);
        $this->assertEquals("test", $result);
    }

    public function testAddColumnOptionsNotNull()
    {
        $options = array('null' => false);
        $result = $this->_conn->addColumnOptions("test", $options);
        $this->assertEquals("test NOT NULL", $result);
    }

    public function testAddColumnNotNullWithoutDefault()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('foo', 'string');
        $table->end();
        $this->_conn->addColumn('testings', 'bar', 'string', array('null' => false, 'default' => ''));

        try {
            $this->_conn->insert("INSERT INTO testings (foo, bar) VALUES ('hello', NULL)");
        } catch (Exception $e) { return; }
        $this->fail('Expected exception wasn\'t raised');

    }

    public function testAddColumnNotNullWithDefault()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('foo', 'string');
        $table->end();

        $this->_conn->insert("INSERT INTO testings (id, foo) VALUES ('1', 'hello')", null, null, 'id', 1);

        $this->_conn->addColumn('testings', 'bar', 'string', array('null' => false, 'default' => 'default'));

        try {
            $this->_conn->insert("INSERT INTO testings (id, foo, bar) VALUES (2, 'hello', NULL)");
        } catch (Exception $e) { return; }
        $this->fail('Expected exception wasn\'t raised');
    }

    public function testAddRemoveSingleField()
    {
        $this->_createTestUsersTable();

        $this->assertFalse(in_array('last_name', $this->_columnNames('users')));

        $this->_conn->addColumn('users', 'last_name', 'string');
        $this->assertTrue(in_array('last_name', $this->_columnNames('users')));

        $this->_conn->removeColumn('users', 'last_name');
        $this->assertFalse(in_array('last_name', $this->_columnNames('users')));
    }

    public function testAddRename()
    {
        $this->_createTestUsersTable();

        $this->_conn->delete('DELETE FROM users');

        $this->_conn->addColumn('users', 'girlfriend', 'string');
        $this->_conn->insert("INSERT INTO users (girlfriend) VALUES ('bobette')");

        $this->_conn->renameColumn('users', 'girlfriend', 'exgirlfriend');

        $bob = (object)$this->_conn->selectOne('SELECT * FROM users');
        $this->assertEquals('bobette', $bob->exgirlfriend);
    }

    public function testDistinct()
    {
        $result = $this->_conn->distinct("test");
        $this->assertEquals("DISTINCT test", $result);
    }

    public function testAddOrderByForAssocLimiting()
    {
        $result = $this->_conn->addOrderByForAssocLimiting("SELECT * FROM documents ",
                                array('order' => 'name DESC'));
        $this->assertEquals("SELECT * FROM documents ORDER BY name DESC", $result);
    }

    public function testInterval()
    {
        $this->assertEquals('INTERVAL \'1 DAY \'',
                            $this->_conn->interval('1 DAY', ''));
    }

    public function testModifyDate()
    {
        $modifiedDate = $this->_conn->modifyDate('mystart', '+', 1, 'DAY');
        $this->assertEquals('mystart + INTERVAL \'1 DAY\'', $modifiedDate);

        $t = $this->_conn->createTable('dates');
        $t->column('mystart', 'datetime');
        $t->column('myend', 'datetime');
        $t->end();
        $this->_conn->insert(
            'INSERT INTO dates (mystart, myend) VALUES (?, ?)',
            array(
                '2011-12-10 00:00:00',
                '2011-12-11 00:00:00'
            )
        );
        $this->assertEquals(
            1,
            $this->_conn->selectValue('SELECT COUNT(*) FROM dates WHERE '
                                      . $modifiedDate . ' = myend')
        );
    }

    public function testBuildClause()
    {
        $this->assertEquals(
            "CASE WHEN CAST(bitmap AS VARCHAR) ~ '^-?[0-9]+$' THEN (CAST(bitmap AS INTEGER) & 2) <> 0 ELSE FALSE END",
            $this->_conn->buildClause('bitmap', '&', 2));
        $this->assertEquals(
            array("CASE WHEN CAST(bitmap AS VARCHAR) ~ '^-?[0-9]+$' THEN (CAST(bitmap AS INTEGER) & ?) <> 0 ELSE FALSE END", array(2)),
            $this->_conn->buildClause('bitmap', '&', 2, true));

        $this->assertEquals(
            "CASE WHEN CAST(bitmap AS VARCHAR) ~ '^-?[0-9]+$' THEN (CAST(bitmap AS INTEGER) | 2) <> 0 ELSE FALSE END",
            $this->_conn->buildClause('bitmap', '|', 2));
        $this->assertEquals(
            array("CASE WHEN CAST(bitmap AS VARCHAR) ~ '^-?[0-9]+$' THEN (CAST(bitmap AS INTEGER) | ?) <> 0 ELSE FALSE END", array(2)),
            $this->_conn->buildClause('bitmap', '|', 2, true));

        $this->assertEquals(
            "name ILIKE '%search%'",
            $this->_conn->buildClause('name', 'LIKE', "search"));
        $this->assertEquals(
            array("name ILIKE ?", array('%search%')),
            $this->_conn->buildClause('name', 'LIKE', "search", true));
        $this->assertEquals(
            "name ILIKE '%search\&replace\?%'",
            $this->_conn->buildClause('name', 'LIKE', "search&replace?"));
        $this->assertEquals(
            array("name ILIKE ?", array('%search&replace?%')),
            $this->_conn->buildClause('name', 'LIKE', "search&replace?", true));
        $this->assertEquals(
            "(name ILIKE 'search\&replace\?%' OR name ILIKE '% search\&replace\?%')",
            $this->_conn->buildClause('name', 'LIKE', "search&replace?", false, array('begin' => true)));
        $this->assertEquals(
            array("(name ILIKE ? OR name ILIKE ?)",
                  array('search&replace?%', '% search&replace?%')),
            $this->_conn->buildClause('name', 'LIKE', "search&replace?", true, array('begin' => true)));

        $this->assertEquals(
            'value = 2',
            $this->_conn->buildClause('value', '=', 2));
        $this->assertEquals(
            array('value = ?', array(2)),
            $this->_conn->buildClause('value', '=', 2, true));
        $this->assertEquals(
            "value = 'foo'",
            $this->_conn->buildClause('value', '=', 'foo'));
        $this->assertEquals(
            array('value = ?', array('foo')),
            $this->_conn->buildClause('value', '=', 'foo', true));
        $this->assertEquals(
            "value = 'foo\?bar'",
            $this->_conn->buildClause('value', '=', 'foo?bar'));
        $this->assertEquals(
            array('value = ?', array('foo?bar')),
            $this->_conn->buildClause('value', '=', 'foo?bar', true));
    }


    /*##########################################################################
    # Table cache
    ##########################################################################*/

    public function testCachedTableIndexes()
    {
        // remove any current cache.
        $this->_cache->set('tables/indexes/cache_table', '');
        $this->assertEquals('', $this->_cache->get('tables/indexes/cache_table'));

        $this->_createTestTable('cache_table');
        $idxs = $this->_conn->indexes('cache_table');

        $this->assertNotEquals('', $this->_cache->get('tables/indexes/cache_table'));
    }

    public function testCachedTableColumns()
    {
        // remove any current cache.
        $this->_cache->set('tables/columns/cache_table', '');
        $this->assertEquals('', $this->_cache->get('tables/columns/cache_table'));

        $this->_createTestTable('cache_table');
        $cols = $this->_conn->columns('cache_table');

        $this->assertNotEquals('', $this->_cache->get('tables/columns/cache_table'));
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Create table to perform tests on
     */
    protected function _createTestTable($name, $options=array())
    {
        $table = $this->_conn->createTable($name, $options);
          $table->column('name',       'string');
          $table->column('is_college', 'boolean');
        $table->end();

        try {
            // make sure table was created
            $sql = "INSERT INTO $name
                    VALUES (1, 'mlb', 'f')";
            $this->_conn->insert($sql);
        } catch (Exception $e) {}
    }

    protected function _createTestUsersTable()
    {
        $table = $this->_conn->createTable('users');
          $table->column('company_id',  'integer',  array('limit' => 11));
          $table->column('name',        'string',   array('limit' => 255, 'default' => ''));
          $table->column('first_name',  'string',   array('limit' => 40, 'default' => ''));
          $table->column('approved',    'boolean',  array('default' => true));
          $table->column('type',        'string',   array('limit' => 255, 'default' => ''));
          $table->column('created_at',  'datetime', array());
          $table->column('created_on',  'date',     array());
          $table->column('updated_at',  'datetime', array());
          $table->column('updated_on',  'date',     array());
        $table->end();
    }

    /**
     * drop test tables
     */
    protected function _dropTestTables()
    {
        $tables = array(
            'binary_testings',
            'cache_table',
            'dates',
            'my_sports',
            'octopi',
            'pk_tests',
            'schema_info',
            'sports',
            'testings',
            'text_to_binary',
            'unit_tests',
            'users',
        );

        foreach ($tables as $table) {
            try {
                $this->_conn->dropTable($table);
            } catch (Exception $e) {}
        }
    }

    protected function _columnNames($tableName)
    {
        $columns = array();
        foreach ($this->_conn->columns($tableName) as $c) {
            $columns[] = $c->getName();
        }
        return $columns;
    }

    /**
     * Get a column by name
     */
    protected function _getColumn($table, $column)
    {
        foreach ($this->_conn->columns($table) as $col) {
            if ($col->getName() == $column) return $col;
        }
    }

    /**
     * Get an index by columns
     */
    protected function _getIndex($table, $indexes)
    {
        $indexes = (array) $indexes;
        sort($indexes);

        foreach ($this->_conn->indexes($table) as $index) {
            $columns = $index->columns;
            sort($columns);
            if ($columns == $indexes) return $index;
        }
    }
}
