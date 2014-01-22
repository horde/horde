<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @group      horde_db
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */
class Horde_Db_Adapter_Oci8Test extends Horde_Db_Adapter_TestBase
{
    public static function setUpBeforeClass()
    {
        if (extension_loaded('oci8')) {
            self::$_skip = false;
            list($conn,) = static::_getConnection();
            if (self::$_skip) {
                return;
            }
            $conn->disconnect();
        }
        self::$_columnTest = new Horde_Db_Adapter_Oracle_ColumnDefinition();
        self::$_tableTest = new Horde_Db_Adapter_Oracle_TestTableDefinition();
    }

    protected static function _getConnection($overrides = array())
    {
        $config = Horde_Test_Case::getConfig('DB_ADAPTER_OCI8_TEST_CONFIG',
                                             null,
                                             array('host' => 'localhost',
                                                   'username' => '',
                                                   'password' => '',
                                                   'dbname' => 'test'));
        if (isset($config['db']['adapter']['oci8']['test'])) {
            $config = $config['db']['adapter']['oci8']['test'];
        }
        if (!is_array($config)) {
            self::$_skip = true;
            self::$_reason = 'No configuration for oci8 test';
            return;
        }
        $config = array_merge($config, $overrides);

        $conn = new Horde_Db_Adapter_Oci8($config);

        $cache = new Horde_Cache(new Horde_Cache_Storage_Mock());
        $conn->setCache($cache);
        //$conn->setLogger(new Horde_Log_Logger(new Horde_Log_Handler_Cli()));
        $conn->reconnect();

        return array($conn, $cache);
    }


    /*##########################################################################
    # Accessor
    ##########################################################################*/

    public function testAdapterName()
    {
        $this->assertEquals('Oracle', $this->_conn->adapterName());
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
        $this->assertEquals('1', $this->_conn->quote(true));
    }

    public function testQuoteFalse()
    {
        $this->assertEquals('0', $this->_conn->quote(false));
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
        $col = new Horde_Db_Adapter_Oracle_Column('age', 'NULL', 'int', true, 11);
        $this->assertEquals('1', $this->_conn->quote(true, $col));
    }


    /*##########################################################################
    # Schema Statements
    ##########################################################################*/

    public function testNativeDatabaseTypes()
    {
        $types = $this->_conn->nativeDatabaseTypes();
        $this->assertEquals(array('name' => 'number', 'limit' => null), $types['integer']);
    }

    public function testTableAliasLength()
    {
        $len = $this->_conn->tableAliasLength();
        $this->assertEquals(30, $len);
    }

    public function testChangeColumnType()
    {
        $this->_createTestTable('sports');
        $beforeChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('number', $beforeChange->getSqlType());

        $this->_conn->changeColumn('sports', 'is_college', 'string');

        $afterChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('varchar2', $afterChange->getSqlType());

        $table = $this->_conn->createTable('text_to_binary');
        $table->column('data', 'text');
        $table->end();
        $this->_conn->insert('INSERT INTO text_to_binary (data) VALUES (?)',
                             array('foo'));

        $this->_conn->changeColumn('text_to_binary', 'data', 'binary');

        $afterChange = $this->_getColumn('text_to_binary', 'data');
        $this->assertEquals('blob', $afterChange->getSqlType());
        $value = $this->_conn->selectValue('SELECT data FROM text_to_binary');
        $this->assertInstanceOf('OCI-Lob', $value);
        $this->assertEquals('foo', $value->read($value->size()));
    }

    public function testChangeColumnLimit()
    {
        $this->_createTestTable('sports');
        $beforeChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('number', $beforeChange->getSqlType());

        $this->_conn->changeColumn('sports', 'is_college', 'string',
                                   array('limit' => '40'));

        $afterChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('varchar2', $afterChange->getSqlType());
        $this->assertEquals(40, $afterChange->getLimit());
    }

    public function testChangeColumnPrecisionScale()
    {
        $this->_createTestTable('sports');
        $beforeChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('number', $beforeChange->getSqlType());

        $this->_conn->changeColumn('sports', 'is_college', 'decimal',
                                   array('precision' => '5', 'scale' => '2'));

        $afterChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('number', $afterChange->getSqlType());
        $this->assertEquals(5, $afterChange->precision());
        $this->assertEquals(2, $afterChange->scale());
    }

    public function testRenameColumn()
    {
        $this->_createTestUsersTable();

        $this->_conn->renameColumn('users', 'first_name', 'nick_name');
        $this->assertTrue(in_array('nick_name', $this->_columnNames('users')));

        $this->_createTestTable('sports');

        $beforeChange = $this->_getColumn('sports', 'is_college');
        $this->assertEquals('number', $beforeChange->getSqlType());

        $this->_conn->renameColumn('sports', 'is_college', 'is_renamed');

        $afterChange = $this->_getColumn('sports', 'is_renamed');
        $this->assertEquals('number', $afterChange->getSqlType());
    }

    public function testIndexNameByMultiColumn()
    {
        $name = $this->_conn->indexName('sports', array('column' =>
                                                array('name', 'is_college')));
        $this->assertEquals('ind_spo_on_nam_and_is_col', $name);
    }

    public function testTypeToSqlTypePrimaryKey()
    {
        $result = $this->_conn->typeToSql('autoincrementKey');
        $this->assertEquals('number NOT NULL PRIMARY KEY', $result);
    }

    public function testTypeToSqlTypeString()
    {
        $result = $this->_conn->typeToSql('string');
        $this->assertEquals('varchar2(255)', $result);
    }

    public function testTypeToSqlTypeText()
    {
        $result = $this->_conn->typeToSql('text');
        $this->assertEquals('clob', $result);
    }

    public function testTypeToSqlTypeBinary()
    {
        $result = $this->_conn->typeToSql('binary');
        $this->assertEquals('blob', $result);
    }

    public function testTypeToSqlTypeFloat()
    {
        $result = $this->_conn->typeToSql('float');
        $this->assertEquals('float', $result);
    }

    public function testTypeToSqlTypeDatetime()
    {
        $result = $this->_conn->typeToSql('datetime');
        $this->assertEquals('date', $result);
    }

    public function testTypeToSqlTypeTimestamp()
    {
        $result = $this->_conn->typeToSql('timestamp');
        $this->assertEquals('date', $result);
    }

    public function testTypeToSqlInt()
    {
        $result = $this->_conn->typeToSql('integer');
        $this->assertEquals('number', $result);
    }

    public function testTypeToSqlIntLimit()
    {
        $result = $this->_conn->typeToSql('integer', '1');
        $this->assertEquals('number(1)', $result);
    }

    public function testTypeToSqlDecimalPrecision()
    {
        $result = $this->_conn->typeToSql('decimal', null, '5');
        $this->assertEquals('number(5)', $result);
    }

    public function testTypeToSqlDecimalScale()
    {
        $result = $this->_conn->typeToSql('decimal', null, '5', '2');
        $this->assertEquals('number(5, 2)', $result);
    }

    public function testTypeToSqlBoolean()
    {
        $result = $this->_conn->typeToSql('boolean');
        $this->assertEquals('number(1)', $result);
    }

    public function testAddColumnOptions()
    {
        $result = $this->_conn->addColumnOptions('test', array());
        $this->assertEquals('test', $result);
    }

    public function testAddColumnOptionsDefault()
    {
        $options = array('default' => '0');
        $result = $this->_conn->addColumnOptions('test', $options);
        $this->assertEquals('test DEFAULT \'0\'', $result);
    }

    public function testAddColumnOptionsNull()
    {
        $options = array('null' => true);
        $result = $this->_conn->addColumnOptions('test', $options);
        $this->assertEquals('test NULL', $result);
    }

    public function testAddColumnOptionsNotNull()
    {
        $options = array('null' => false);
        $result = $this->_conn->addColumnOptions('test', $options);
        $this->assertEquals('test NOT NULL', $result);
    }

    public function testModifyDate()
    {
        $modifiedDate = $this->_conn->modifyDate('mystart', '+', 1, 'DAY');
        $this->assertEquals('mystart + INTERVAL \'1\' DAY', $modifiedDate);

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
            'BITAND(bitmap, 2)',
            $this->_conn->buildClause('bitmap', '&', 2));
        $this->assertEquals(
            array('BITAND(bitmap, ?)', array(2)),
            $this->_conn->buildClause('bitmap', '&', 2, true));

        $this->assertEquals(
            'bitmap + 2 - BITAND(bitmap, 2)',
            $this->_conn->buildClause('bitmap', '|', 2));
        $this->assertEquals(
            array('bitmap + ? - BITAND(bitmap, ?)', array(2, 2)),
            $this->_conn->buildClause('bitmap', '|', 2, true));

        $this->assertEquals(
            "LOWER(name) LIKE LOWER('%search%')",
            $this->_conn->buildClause('name', 'LIKE', 'search'));
        $this->assertEquals(
            array('LOWER(name) LIKE LOWER(?)', array('%search%')),
            $this->_conn->buildClause('name', 'LIKE', 'search', true));
        $this->assertEquals(
            "LOWER(name) LIKE LOWER('%search\&replace\?%')",
            $this->_conn->buildClause('name', 'LIKE', 'search&replace?'));
        $this->assertEquals(
            array('LOWER(name) LIKE LOWER(?)', array('%search&replace?%')),
            $this->_conn->buildClause('name', 'LIKE', 'search&replace?', true));
        $this->assertEquals(
            "(LOWER(name) LIKE LOWER('search\&replace\?%') OR LOWER(name) LIKE LOWER('% search\&replace\?%'))",
            $this->_conn->buildClause('name', 'LIKE', 'search&replace?', false, array('begin' => true)));
        $this->assertEquals(
            array('(LOWER(name) LIKE LOWER(?) OR LOWER(name) LIKE LOWER(?))',
                  array('search&replace?%', '% search&replace?%')),
            $this->_conn->buildClause('name', 'LIKE', 'search&replace?', true, array('begin' => true)));

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
}
