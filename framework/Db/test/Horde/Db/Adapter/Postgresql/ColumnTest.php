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
class Horde_Db_Adapter_Postgresql_ColumnTest extends Horde_Db_Adapter_ColumnBase
{
    protected $_class = 'Horde_Db_Adapter_Postgresql_Column';


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

    public function testTypeString()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', 'NULL', 'character varying(255)');
        $this->assertEquals('string', $col->getType());
    }


    /*##########################################################################
    # Extract Limit
    ##########################################################################*/

    public function testExtractLimitVarchar()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('test', 'NULL', 'character varying(255)');
        $this->assertEquals(255, $col->getLimit());
    }


    /*##########################################################################
    # Type Cast Values
    ##########################################################################*/

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

    public function testDefaultString()
    {
        $col = new Horde_Db_Adapter_Postgresql_Column('name', '', 'character varying(255)');
        $this->assertEquals('', $col->getDefault());
    }
}
