<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Ralf Lang <lang@b1-systems.de>
 * @category   Horde
 * @package    Rdo
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Rdo_Test_Sql_Base extends Horde_Rdo_Test_Base
{
    protected static $db;
    protected static $BaseObjectMapper;
    protected static $RelatedThingMapper;

    public static function setUpBeforeClass()
    {
        require_once dirname(__FILE__) . '/../migration/sql.php';
        migrate_sql(self::$db);
        // read sql file for statements
        $statements = array();
        $current_stmt = '';
        $fp = fopen(dirname(__FILE__) . '/../fixtures/unit_tests.sql', 'r');
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
            self::$db->execute($stmt);
        }

    }

    public function testFindReturnsHordeRdoList()
    {
        $result = self::$BaseObjectMapper->find();
        $this->assertTrue($result instanceof Horde_Rdo_List, "find() returns a Horde_Rdo_List");
    }

    public function testFindOneReturnsEntity()
    {
        $result = self::$BaseObjectMapper->findOne();
        $this->assertTrue($result instanceof Horde_Rdo_Base, "findOne() returns a Horde_Rdo_Base");
    }
    public function testFindOneWithScalarReturnsEntityWithKeyValue()
    {
        $result = self::$BaseObjectMapper->findOne(2);
        $this->assertEquals(2, $result->baseobject_id, "findOne() returns the right Horde_Rdo_Base if key is given as argument");
    }

    public function testToOneRelationRetrievesEntityWhenKeyIsFound()
    {
        $entity = self::$BaseObjectMapper->findOne(1);
        $this->assertTrue($entity->relatedthing instanceof Horde_Rdo_Test_Objects_RelatedThing, "to-one-relations return an instance object");
    }

    public function testToOneRelationReturnsNullWhenKeyIsNotFound()
    {
        $entity = self::$BaseObjectMapper->findOne(3);
        $this->assertNull($entity->relatedthing, "to-one-relations return null when relation key is not found");
    }

    public function testToOneRelationReturnsNullWhenKeyIsNull()
    {
        $entity = self::$BaseObjectMapper->findOne(4);
        $this->assertEquals(100, $entity->relatedthing->relatedthing_intproperty, "to-one-relations return an instance object if relation key is null");
    }

    public function testToOneRelationRetrievesCorrectEntityWhenKeyIsFound()
    {
        $result = self::$BaseObjectMapper->findOne(1);
        $this->assertEquals(100, $result->relatedthing->relatedthing_intproperty, "to-one-relations return correct related object when key is found");
    }

    public static function tearDownAfterClass()
    {
        if (self::$db) {
            $migration = new Horde_Db_Migration_Base(self::$db);
            $migration->dropTable('test_somebaseobjects');
            $migration->dropTable('test_relatedthings');
            self::$db = null;
        }
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped('No sqlite extension or no sqlite PDO driver.');
        }

       self::$BaseObjectMapper = new Horde_Rdo_Test_Objects_SomeBaseObjectMapper(self::$db);
    }
}
