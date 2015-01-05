<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2015 Horde LLC (http://www.horde.org/)
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
abstract class Horde_Db_Adapter_ColumnBase extends Horde_Test_Case
{
    protected $_class;

    /*##########################################################################
    # Construction
    ##########################################################################*/

    public function testDefaultNull()
    {
        $col = new $this->_class('name', 'NULL', 'varchar(255)');
        $this->assertEquals(true, $col->isNull());
    }

    public function testNotNull()
    {
        $col = new $this->_class('name', 'NULL', 'varchar(255)', false);
        $this->assertEquals(false, $col->isNull());
    }

    public function testName()
    {
        $col = new $this->_class('name', 'NULL', 'varchar(255)');
        $this->assertEquals('name', $col->getName());
    }

    public function testSqlType()
    {
        $col = new $this->_class('name', 'NULL', 'varchar(255)');
        $this->assertEquals('varchar(255)', $col->getSqlType());
    }

    public function testIsText()
    {
        $col = new $this->_class('test', 'NULL', 'varchar(255)');
        $this->assertTrue($col->isText());
        $col = new $this->_class('test', 'NULL', 'text');
        $this->assertTrue($col->isText());

        $col = new $this->_class('test', 'NULL', 'int(11)');
        $this->assertFalse($col->isText());
        $col = new $this->_class('test', 'NULL', 'float(11,1)');
        $this->assertFalse($col->isText());
    }

    public function testIsNumber()
    {
        $col = new $this->_class('test', 'NULL', 'varchar(255)');
        $this->assertFalse($col->isNumber());
        $col = new $this->_class('test', 'NULL', 'text');
        $this->assertFalse($col->isNumber());

        $col = new $this->_class('test', 'NULL', 'int(11)');
        $this->assertTrue($col->isNumber());
        $col = new $this->_class('test', 'NULL', 'float(11,1)');
        $this->assertTrue($col->isNumber());
    }


    /*##########################################################################
    # Types
    ##########################################################################*/

    public function testTypeInteger()
    {
        $col = new $this->_class('age', 'NULL', 'int(11)');
        $this->assertEquals('integer', $col->getType());
    }

    public function testTypeFloat()
    {
        $col = new $this->_class('age', 'NULL', 'float(11,1)');
        $this->assertEquals('float', $col->getType());
    }

    public function testTypeDecimalPrecisionNone()
    {
        $col = new $this->_class('age', 'NULL', 'decimal(11,0)');
        $this->assertEquals('integer', $col->getType());
    }

    public function testTypeDecimal()
    {
        $col = new $this->_class('age', 'NULL', 'decimal(11,1)');
        $this->assertEquals('decimal', $col->getType());
    }

    public function testTypeDatetime()
    {
        $col = new $this->_class('age', 'NULL', 'datetime');
        $this->assertEquals('datetime', $col->getType());
    }

    public function testTypeTimestamp()
    {
        $col = new $this->_class('age', 'CURRENT_TIMESTAMP', 'timestamp');
        $this->assertEquals('timestamp', $col->getType());
    }

    public function testTypeTime()
    {
        $col = new $this->_class('age', 'NULL', 'time');
        $this->assertEquals('time', $col->getType());
    }

    public function testTypeDate()
    {
        $col = new $this->_class('age', 'NULL', 'date');
        $this->assertEquals('date', $col->getType());
    }

    public function testTypeText()
    {
        $col = new $this->_class('age', 'NULL', 'text');
        $this->assertEquals('text', $col->getType());
    }

    public function testTypeBinary()
    {
        $col = new $this->_class('age', 'NULL', 'blob(255)');
        $this->assertEquals('binary', $col->getType());
    }

    public function testTypeString()
    {
        $col = new $this->_class('name', 'NULL', 'varchar(255)');
        $this->assertEquals('string', $col->getType());
    }


    /*##########################################################################
    # Extract Limit
    ##########################################################################*/

    public function testExtractLimitInt()
    {
        $col = new $this->_class('test', 'NULL', 'int(11)');
        $this->assertEquals(11, $col->getLimit());
    }

    public function testExtractLimitVarchar()
    {
        $col = new $this->_class('test', 'NULL', 'varchar(255)');
        $this->assertEquals(255, $col->getLimit());
    }

    public function testExtractLimitDecimal()
    {
        $col = new $this->_class('test', 'NULL', 'decimal(11,1)');
        $this->assertEquals('11', $col->getLimit());
    }

    public function testExtractLimitText()
    {
        $col = new $this->_class('test', 'NULL', 'text');
        $this->assertEquals(null, $col->getLimit());
    }

    public function testExtractLimitNone()
    {
        $col = new $this->_class('test', 'NULL');
        $this->assertEquals(null, $col->getLimit());
    }


    /*##########################################################################
    # Extract Precision/Scale
    ##########################################################################*/

    public function testExtractPrecisionScale()
    {
        $col = new $this->_class('test', 'NULL', 'decimal(12,1)');
        $this->assertEquals('12', $col->precision());
        $this->assertEquals('1',  $col->scale());
    }


    /*##########################################################################
    # Type Cast Values
    ##########################################################################*/

    public function testTypeCastInteger()
    {
        $col = new $this->_class('name', '1', 'int(11)', false);
        $this->assertEquals(1, $col->getDefault());
    }

    public function testTypeCastFloat()
    {
        $col = new $this->_class('version', '1.0', 'float(11,1)', false);
        $this->assertEquals(1.0, $col->getDefault());
    }

    public function testTypeCastString()
    {
        $col = new $this->_class('name', 'n/a', 'varchar(255)', false);
        $this->assertEquals('n/a', $col->getDefault());
    }

    abstract public function testTypeCastBooleanFalse();

    abstract public function testTypeCastBooleanTrue();


    /*##########################################################################
    # Defaults
    ##########################################################################*/

    public function testDefaultDatetime()
    {
        $col = new $this->_class('name', '', 'datetime');
        $this->assertEquals(null, $col->getDefault());
    }

    public function testDefaultInteger()
    {
        $col = new $this->_class('name', '', 'int(11)');
        $this->assertEquals(null, $col->getDefault());
    }

    public function testDefaultString()
    {
        $col = new $this->_class('name', '', 'varchar(255)');
        $this->assertEquals('', $col->getDefault());
    }

    public function testDefaultText()
    {
        $col = new $this->_class('name', '', 'text');
        $this->assertEquals('', $col->getDefault());
    }

    public function testDefaultBinary()
    {
        $col = new $this->_class('name', '', 'blob(255)');
        $this->assertEquals('', $col->getDefault());
    }

}
