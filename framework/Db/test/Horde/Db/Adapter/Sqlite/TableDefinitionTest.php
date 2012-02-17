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
class Horde_Db_Adapter_Sqlite_TableDefinitionTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        list($this->_conn,) = Horde_Db_AllTests::$connFactory->getConnection();
    }

    protected function tearDown()
    {
        // close connection
        $this->_conn->disconnect();
    }


    /*##########################################################################
    # Public methods
    ##########################################################################*/

    public function testConstruct()
    {
    }

    public function testName()
    {
    }

    public function testGetOptions()
    {
    }

    public function testPrimaryKey()
    {
    }

    public function testColumn()
    {
    }

    public function testToSql()
    {
    }

    /*##########################################################################
    # Array Access
    ##########################################################################*/

    public function testOffsetExists()
    {
    }

    public function testOffsetGet()
    {
    }

    public function testOffsetSet()
    {
    }

    public function testOffsetUnset()
    {
    }
}
