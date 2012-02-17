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
class Horde_Db_Adapter_Postgresql_ColumnTest extends PHPUnit_Framework_TestCase
{
    /*##########################################################################
    # Construction
    ##########################################################################*/

    public function testDefaultNull()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', 'NULL', 'character varying(255)');
        $this->assertEquals(true, $col->isNull());
    }

    public function testNotNull()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', 'NULL', 'character varying(255)', false);
        $this->assertEquals(false, $col->isNull());
    }

    public function testName()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', 'NULL', 'character varying(255)');
        $this->assertEquals('name', $col->getName());
    }

    public function testSqlType()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', 'NULL', 'character varying(255)');
        $this->assertEquals('character varying(255)', $col->getSqlType());
    }

    public function testIsText()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'character varying(255)');
        $this->assertTrue($col->isText());
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'text');
        $this->assertTrue($col->isText());

        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'int(11)');
        $this->assertFalse($col->isText());
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'float(11,1)');
        $this->assertFalse($col->isText());
    }

    public function testIsNumber()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'character varying(255)');
        $this->assertFalse($col->isNumber());
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'text');
        $this->assertFalse($col->isNumber());

        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'int(11)');
        $this->assertTrue($col->isNumber());
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'float(11,1)');
        $this->assertTrue($col->isNumber());
    }


    /*##########################################################################
    # Types
    ##########################################################################*/

    public function testTypeInteger()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'int(11)');
        $this->assertEquals('integer', $col->getType());
    }

    public function testTypeFloat()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'float(11,1)');
        $this->assertEquals('float', $col->getType());
    }

    public function testTypeDecimalPrecisionNone()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'decimal(11,0)');
        $this->assertEquals('integer', $col->getType());
    }

    public function testTypeDecimal()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'decimal(11,1)');
        $this->assertEquals('decimal', $col->getType());
    }

    public function testTypeDatetime()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'datetime');
        $this->assertEquals('datetime', $col->getType());
    }

    public function testTypeTimestamp()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'CURRENT_TIMESTAMP', 'timestamp');
        $this->assertEquals('timestamp', $col->getType());
    }

    public function testTypeTime()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'time');
        $this->assertEquals('time', $col->getType());
    }

    public function testTypeDate()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'date');
        $this->assertEquals('date', $col->getType());
    }

    public function testTypeText()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'text');
        $this->assertEquals('text', $col->getType());
    }

    public function testTypeBinary()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('age', 'NULL', 'blob(255)');
        $this->assertEquals('binary', $col->getType());
    }

    public function testTypeString()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', 'NULL', 'character varying(255)');
        $this->assertEquals('string', $col->getType());
    }


    /*##########################################################################
    # Extract Limit
    ##########################################################################*/

    public function testExtractLimitInt()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'int(11)');
        $this->assertEquals(11, $col->getLimit());
    }

    public function testExtractLimitVarchar()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'character varying(255)');
        $this->assertEquals(255, $col->getLimit());
    }

    public function testExtractLimitDecimal()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'decimal(11,1)');
        $this->assertEquals('11', $col->getLimit());
    }

    public function testExtractLimitText()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'text');
        $this->assertEquals(null, $col->getLimit());
    }

    public function testExtractLimitNone()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL');
        $this->assertEquals(null, $col->getLimit());
    }

    /*##########################################################################
    # Extract Precision/Scale
    ##########################################################################*/

    public function testExtractPrecisionScale()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'decimal(12,1)');
        $this->assertEquals('12', $col->precision());
        $this->assertEquals('1',  $col->scale());
    }


    /*##########################################################################
    # Type Cast Values
    ##########################################################################*/

    public function testTypeCastInteger()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', '1', 'int(11)', false);
        $this->assertEquals(1, $col->getDefault());
    }

    public function testTypeCastFloat()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('version', '1.0', 'float(11,1)', false);
        $this->assertEquals(1.0, $col->getDefault());
    }

    public function testTypeCastString()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', "'n/a'::character varying", 'character varying(255)', false);
        $this->assertEquals('n/a', $col->getDefault());
    }

    public function testTypeCastBooleanFalse()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('is_active', '0', 'boolean', false);
        $this->assertSame(false, $col->getDefault());
    }

    public function testTypeCastBooleanTrue()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('is_active', '1', 'boolean', false);
        $this->assertSame(true, $col->getDefault());
    }

    /*##########################################################################
    # Column Types
    ##########################################################################*/

    /*@TODO tests for PostgreSQL-specific column types */


    /*##########################################################################
    # Defaults
    ##########################################################################*/

    public function testDefaultDatetime()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', '', 'datetime');
        $this->assertEquals(null, $col->getDefault());
    }

    public function testDefaultInteger()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', '', 'int(11)');
        $this->assertEquals(null, $col->getDefault());
    }

    public function testDefaultString()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', '', 'character varying(255)');
        $this->assertEquals('', $col->getDefault());
    }

    public function testDefaultText()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', '', 'text');
        $this->assertEquals('', $col->getDefault());
    }

    public function testDefaultBinary()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', '', 'blob(255)');
        $this->assertEquals('', $col->getDefault());
    }

}
