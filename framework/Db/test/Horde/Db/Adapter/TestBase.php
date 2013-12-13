<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @group      horde_db
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */
abstract class Horde_Db_Adapter_TestBase extends Horde_Test_Case
{
    protected static $_columnTest;

    protected static $_tableTest;

    protected static $_skip = true;

    protected static $_reason = 'The MySQL adapter is not available';

    protected $_conn;

    static protected function _getConnection($overrides = array())
    {
        throw new LogicException('_getConnection() must be implemented in a sub-class.');
    }

    protected function setUp()
    {
        if (self::$_skip ||
            !($res = static::_getConnection())) {
            $this->markTestSkipped(self::$_reason);
        }

        list($this->_conn, $this->_cache) = $res;
        self::$_columnTest->conn = $this->_conn;
        self::$_tableTest->conn = $this->_conn;

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
          $table->column('time_value',      'time',     array());
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
        $fp = fopen(__DIR__ . '/../fixtures/unit_tests.sql', 'r');
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
            $this->_conn->insert($stmt);
        }
    }

    protected function tearDown()
    {
        // clean up
        $this->_dropTestTables();

        // close connection
        $this->_conn->disconnect();
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

    abstract public function testAdapterName();

    abstract public function testSupportsMigrations();

    abstract public function testSupportsCountDistinct();

    abstract public function testSupportsInterval();


    /*##########################################################################
    # Database Statements
    ##########################################################################*/

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
        $this->assertArrayHasKey('id', $result);
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

    abstract public function testQuoteNull();

    abstract public function testQuoteTrue();

    abstract public function testQuoteFalse();

    abstract public function testQuoteInteger();

    abstract public function testQuoteFloat();

    abstract public function testQuoteString();

    abstract public function testQuoteDirtyString();

    abstract public function testQuoteColumnName();

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

    abstract public function testNativeDatabaseTypes();

    abstract public function testTableAliasLength();

    public function testTableAliasFor()
    {
        $alias = $this->_conn->tableAliasFor('my_table_name');
        $this->assertEquals('my_table_name', $alias);
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
        $this->assertEquals('',        $col->getDefault());
        $this->assertEquals(false,     $col->isText());
        $this->assertEquals(true,      $col->isNumber());

        return $col;
    }

    public function testCreateTableWithSeparatePk()
    {
        $table = $this->_conn->createTable('testings', array('autoincrementKey' => false));
        $table->column('foo', 'autoincrementKey');
        $table->column('bar', 'integer');
        $table->end();

        $pkColumn = $table['foo'];

        $this->_conn->insert('INSERT INTO testings (bar) VALUES (1)');

        $sql = 'SELECT * FROM testings WHERE foo = 1';
        $result = $this->_conn->select($sql);
        $this->assertEquals(1, count($result));

        // Manually insert a primary key value.
        $this->_conn->insert('INSERT INTO testings (foo, bar) VALUES (2, 1)');
        $this->_conn->insert('INSERT INTO testings (bar) VALUES (1)');

        return $pkColumn;
    }

    abstract public function testChangeColumnType();

    abstract public function testChangeColumnLimit();

    abstract public function testChangeColumnPrecisionScale();

    abstract public function testRenameColumn();

    public function testRenameColumnWithSqlReservedWord()
    {
        $this->_createTestUsersTable();

        $this->_conn->renameColumn('users', 'first_name', 'other_name');
        $this->assertTrue(in_array('other_name', $this->_columnNames('users')));
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
                                        array('name' => 'named_admin'));

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

    abstract public function testTypeToSqlTypePrimaryKey();

    abstract public function testTypeToSqlTypeString();

    abstract public function testTypeToSqlTypeText();

    abstract public function testTypeToSqlTypeBinary();

    abstract public function testTypeToSqlTypeFloat();

    abstract public function testTypeToSqlTypeDatetime();

    abstract public function testTypeToSqlTypeTimestamp();

    abstract public function testTypeToSqlInt();

    abstract public function testTypeToSqlIntLimit();

    abstract public function testTypeToSqlDecimalPrecision();

    abstract public function testTypeToSqlDecimalScale();

    abstract public function testTypeToSqlBoolean();

    abstract public function testAddColumnOptions();

    abstract public function testAddColumnOptionsDefault();

    abstract public function testAddColumnOptionsNull();

    abstract public function testAddColumnOptionsNotNull();

    public function testAddColumnNotNullWithoutDefault()
    {
        $table = $this->_conn->createTable('testings');
        $table->column('foo', 'string');
        $table->end();
        $this->_conn->addColumn('testings', 'bar', 'string', array('null' => false, 'default' => ''));

        try {
            $this->_conn->insert("INSERT INTO testings (foo, bar) VALUES ('hello', NULL)");
        } catch (Exception $e) {
            return;
        }
        $this->fail('Expected exception wasn\'t raised');
    }

    public function testAddColumnNotNullWithDefault()
    {
        $table = $this->_conn->createTable('testings');
            $table->column('foo', 'string');
        $table->end();

        $this->_conn->insert("INSERT INTO testings (id, foo) VALUES ('1', 'hello')");

        $this->_conn->addColumn('testings', 'bar', 'string', array('null' => false, 'default' => 'default'));

        try {
            $this->_conn->insert("INSERT INTO testings (id, foo, bar) VALUES (2, 'hello', NULL)");
        } catch (Exception $e) {
            return;
        }
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
        $result = $this->_conn->distinct('test');
        $this->assertEquals('DISTINCT test', $result);
    }

    public function testAddOrderByForAssocLimiting()
    {
        $result = $this->_conn->addOrderByForAssocLimiting('SELECT * FROM documents ',
                                array('order' => 'name DESC'));
        $this->assertEquals('SELECT * FROM documents ORDER BY name DESC', $result);
    }

    abstract public function testModifyDate();

    abstract public function testBuildClause();

    public function testInsertAndReadInUtf8()
    {
        list($conn,) = static::_getConnection(array('charset' => 'utf8'));
        $table = $conn->createTable('charset_utf8');
            $table->column('text', 'string');
        $table->end();

        $input = file_get_contents(__DIR__ . '/../fixtures/charsets/utf8.txt');
        $conn->insert('INSERT INTO charset_utf8 (text) VALUES (?)', array($input));
        $output = $conn->selectValue('SELECT text FROM charset_utf8');

        $this->assertEquals($input, $output);
    }


    /*##########################################################################
    # Table cache
    ##########################################################################*/

    public function testCachedTableIndexes()
    {
        // remove any current cache.
        $this->_cache->set('tables/indexes/cache_table', '');
        $this->assertEquals('', $this->_cache->get('tables/indexes/cache_table', 0));

        $this->_createTestTable('cache_table');
        $idxs = $this->_conn->indexes('cache_table');

        $this->assertNotEquals('', $this->_cache->get('tables/indexes/cache_table', 0));
    }

    public function testCachedTableColumns()
    {
        // remove any current cache.
        $this->_cache->set('tables/columns/cache_table', '');
        $this->assertEquals('', $this->_cache->get('tables/columns/cache_table', 0));

        $this->_createTestTable('cache_table');
        $cols = $this->_conn->columns('cache_table');

        $this->assertNotEquals('', $this->_cache->get('tables/columns/cache_table', 0));
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Create table to perform tests on
     */
    protected function _createTestTable($name, $options = array())
    {
        $table = $this->_conn->createTable($name, $options);
        $table->column('name',       'string');
        $table->column('is_college', 'boolean');
        $table->end();
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
            /* MySQL only? */
            'charset_cp1257',
            /* MySQL only? */
            'charset_utf8',
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

    public function testColumnConstruct()
    {
        self::$_columnTest->testConstruct();
    }

    public function testColumnToSql()
    {
        self::$_columnTest->testToSql();
    }

    public function testColumnToSqlLimit()
    {
        self::$_columnTest->testToSqlLimit();
    }

    public function testColumnToSqlPrecisionScale()
    {
        self::$_columnTest->testToSqlPrecisionScale();
    }

    public function testColumnToSqlNotNull()
    {
        self::$_columnTest->testToSqlNotNull();
    }

    public function testColumnToSqlDefault()
    {
        self::$_columnTest->testToSqlDefault();
    }

    public function testTableConstruct()
    {
        self::$_tableTest->testConstruct();
    }

    public function testTableName()
    {
        self::$_tableTest->testName();
    }

    public function testTableGetOptions()
    {
        self::$_tableTest->testGetOptions();
    }

    public function testTablePrimaryKey()
    {
        self::$_tableTest->testPrimaryKey();
    }

    public function testTableColumn()
    {
        self::$_tableTest->testColumn();
    }

    public function testTableToSql()
    {
        self::$_tableTest->testToSql();
    }
}
