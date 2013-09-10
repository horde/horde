<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
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
class Horde_Db_Adapter_Pdo_PgsqlTest extends Horde_Db_Adapter_TestBase
{
    public static function setUpBeforeClass()
    {
        if (extension_loaded('pdo') &&
            in_array('pgsql', PDO::getAvailableDrivers())) {
            self::$_skip = false;
            list($conn,) = static::_getConnection();
            $conn->disconnect();
        }
        self::$_columnTest = new Horde_Db_Adapter_Postgresql_ColumnDefinition();
        self::$_tableTest = new Horde_Db_Adapter_Postgresql_TableDefinition();
    }

    protected static function _getConnection($overrides = array())
    {
        $config = Horde_Test_Case::getConfig('DB_ADAPTER_PDO_PGSQL_TEST_CONFIG',
                                             null,
                                             array('username' => '',
                                                   'password' => '',
                                                   'dbname' => 'test'));
        if (isset($config['db']['adapter']['pdo']['pgsql']['test'])) {
            $config = $config['db']['adapter']['pdo']['pgsql']['test'];
        }
        if (!is_array($config)) {
            self::$_skip = true;
            self::$_reason = 'No configuration for pdo_pgsql test';
            return;
        }
        $config = array_merge($config, $overrides);

        $conn = new Horde_Db_Adapter_Pdo_Pgsql($config);

        $cache = new Horde_Cache(new Horde_Cache_Storage_Mock());
        $conn->setCache($cache);

        return array($conn, $cache);
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

    public function testSupportsInterval()
    {
        $this->assertTrue($this->_conn->supportsInterval());
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

    public function testColumns()
    {
        $col = parent::testColumns();
        $this->assertEquals(null,      $col->getLimit());
        $this->assertEquals('integer', $col->getSqlType());
    }

    public function testCreateTableWithSeparatePk()
    {
        $pkColumn = parent::testCreateTableWithSeparatePk();
        $this->assertEquals('"foo" serial primary key', $pkColumn->toSql());
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
                             array("foo"));

        $this->_conn->changeColumn('text_to_binary', 'data', 'binary');

        $afterChange = $this->_getColumn('text_to_binary', 'data');
        $this->assertEquals('bytea', $afterChange->getSqlType());
        $this->assertEquals(
            "foo",
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
    # Protected
    ##########################################################################*/

    /**
     * Create table to perform tests on
     */
    protected function _createTestTable($name, $options = array())
    {
        parent::_createTestTable($name, $options = array());
        try {
            // make sure table was created
            $sql = "INSERT INTO $name
                    VALUES (1, 'mlb', 'f')";
            $this->_conn->insert($sql);
        } catch (Exception $e) {
        }
    }
}
