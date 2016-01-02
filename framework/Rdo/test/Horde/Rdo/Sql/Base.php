<?php
/**
 * Copyright 2010-2016 Horde LLC (http://www.horde.org/)
 *
 * @author     Ralf Lang <lang@b1-systems.de>
 * @category   Horde
 * @package    Rdo
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Rdo_Test_Sql_Base extends Horde_Test_Case
{
    protected static $db;
    protected static $EagerBaseObjectMapper;
    protected static $LazyBaseObjectMapper;
    protected static $RelatedThingMapper;
    protected static $MtmaMapper;
    protected static $MtmbMapper;

    protected static function _migrate_sql_rdo($db)
    {
        $migration = new Horde_Db_Migration_Base($db);

        /* Cleanup potential left-overs. */
        try {
            $migration->dropTable('test_someeagerbaseobjects');
            $migration->dropTable('test_somelazybaseobjects');
            $migration->dropTable('test_relatedthings');
            $migration->dropTable('test_manytomanya');
            $migration->dropTable('test_manytomanyb');
            $migration->dropTable('test_manythrough');
        } catch (Horde_Db_Exception $e) {
        }

        $t = $migration->createTable('test_someeagerbaseobjects', array('autoincrementKey' => 'baseobject_id'));
        $t->column('relatedthing_id', 'integer');
        $t->column('atextproperty', 'string');
        $t->end();

        $t = $migration->createTable('test_somelazybaseobjects', array('autoincrementKey' => 'baseobject_id'));
        $t->column('relatedthing_id', 'integer');
        $t->column('atextproperty', 'string');
        $t->end();

        $t = $migration->createTable('test_relatedthings', array('autoincrementKey' => 'relatedthing_id'));
        $t->column('relatedthing_textproperty', 'string', array('limit' => 255, 'null' => false));
        $t->column('relatedthing_intproperty', 'integer', array('null' => false));
        $t->end();

        $t = $migration->createTable('test_manytomanya', array('autoincrementKey' => 'a_id'));
        $t->column('a_intproperty', 'integer', array('null' => false));
        $t->end();

        $t = $migration->createTable('test_manytomanyb', array('autoincrementKey' => 'b_id'));
        $t->column('b_intproperty', 'integer', array('null' => false));
        $t->end();


        $t = $migration->createTable('test_manythrough');
        $t->column('a_id', 'integer');
        $t->column('b_id', 'integer');
        $t->end();

        $migration->migrate('up');
    }


    public static function setUpBeforeClass()
    {
        self::_migrate_sql_rdo(self::$db);
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
            self::$db->execute($stmt);
        }

    }

    public function testListOffsetExistsReturnFalseForTooBigOffset()
    {
        $list = self::$LazyBaseObjectMapper->find();
        $this->assertFalse(isset($list[$list->count()]), "return false for index not in list");
    }

    public function testListOffsetExistsReturnFalseFor0inemptylist()
    {
        $list = self::$LazyBaseObjectMapper->find(5000);
        $this->assertFalse(isset($list[0]), "return false for first index in empty list");
    }

    public function testHasRelationManyToManyAny()
    {
        $objectA = self::$MtmaMapper->findOne(2);
        $this->assertTrue($objectA->hasRelation('manybs'));
    }

    public function testHasRelationManyToManyAnyButEmpty()
    {
        $objectA = self::$MtmaMapper->findOne(1);
        $this->assertFalse($objectA->hasRelation('manybs'));
    }

    public function testHasRelationManyToManyWrongPeer()
    {
        $objectA = self::$MtmaMapper->findOne(2);
        $objectB = self::$MtmbMapper->findOne(11);
        $this->assertFalse($objectA->hasRelation('manybs', $objectB));
    }

    public function testHasRelationManyToManyRightPeer()
    {
        $objectA = self::$MtmaMapper->findOne(2);
        $objectB = self::$MtmbMapper->findOne(12);
        $this->assertTrue($objectA->hasRelation('manybs', $objectB));
    }

    public function testHasRelationOneToOne()
    {
        $objectA1 = self::$LazyBaseObjectMapper->findOne(1);
        $objectA2 = self::$LazyBaseObjectMapper->findOne(4);
        $objectB1 = self::$LazyBaseObjectMapper->findOne(1);

        $this->assertTrue($objectA1->hasRelation('lazyRelatedThing', $objectB1));
        $this->assertFalse($objectA2->hasRelation('lazyRelatedThing', $objectB1));
        $this->assertTrue($objectA1->hasRelation('lazyRelatedThing'));
        $this->assertFalse($objectA2->hasRelation('lazyRelatedThing'));
    }

    public function testAddRelationManyToMany()
    {
        $objectA = self::$MtmaMapper->findOne(1);
        $objectB = self::$MtmbMapper->findOne(11);
        $objectA->addRelation('manybs', $objectB);
        $this->assertTrue($objectA->hasRelation('manybs', $objectB));
    }

    public function testRemoveRelationManyToManyOne()
    {
        $objectA = self::$MtmaMapper->findOne(2);
        $objectB1 = self::$MtmbMapper->findOne(12);
        $objectB2 = self::$MtmbMapper->findOne(14);
        $objectA->removeRelation('manybs', $objectB1);
        $this->assertFalse($objectA->hasRelation('manybs', $objectB1));
        $this->assertTrue($objectA->hasRelation('manybs', $objectB2));
    }

    public function testRemoveRelationManyToManyAll()
    {
        $objectA = self::$MtmaMapper->findOne(2);
        $objectB1 = self::$MtmbMapper->findOne(12);
        $objectB2 = self::$MtmbMapper->findOne(14);
        $objectA->removeRelation('manybs');
        $this->assertFalse($objectA->hasRelation('manybs', $objectB1));
        $this->assertFalse($objectA->hasRelation('manybs', $objectB2));
    }

    public function testListOffsetExistsReturnTrueForFirst()
    {
        $list = self::$LazyBaseObjectMapper->find();
        $this->assertTrue(isset($list[0]), "return true for first index in list");
    }

    public function testListOffsetExistsReturnTrueForLast()
    {
        $list = self::$LazyBaseObjectMapper->find();
        $this->assertTrue(isset($list[$list->count() - 1]), "return true for last index in list");
    }

    public function testListOffsetGetReturnNullForTooBig()
    {
        $list = self::$LazyBaseObjectMapper->find();
        $this->assertNull($list[$list->count()], "return Null for one after last index in list");
    }

    public function testListOffsetGetReturnObjectForLast()
    {
        $list = self::$LazyBaseObjectMapper->find();
        $this->assertTrue($list[$list->count()-1] instanceof Horde_Rdo_Test_Objects_SomeLazyBaseObject, "return Object for last index in list");
    }

    public function testListOffsetGetReturnObjectForFirst()
    {
        $list = self::$LazyBaseObjectMapper->find();
        $this->assertTrue($list[0] instanceof Horde_Rdo_Test_Objects_SomeLazyBaseObject, "return Object for first index in list");
    }

   /**
    * @expectedException Horde_Rdo_Exception
    */
    public function testListOffsetSetThrowException()
    {
        $list = self::$LazyBaseObjectMapper->find();
        $list[0] = $list[0];
        $this->assertTrue($list[0] instanceof Horde_Rdo_Test_Objects_SomeLazyBaseObject, "Throw exception when trying to set a new element to the list");
    }

   /**
    * @expectedException Horde_Rdo_Exception
    */
    public function testListOffsetUnsetThrowException()
    {
        $list = self::$LazyBaseObjectMapper->find();
        unset($list[0]);
        $this->assertTrue($list[0] instanceof Horde_Rdo_Test_Objects_SomeLazyBaseObject, "Throw exception when trying to unset an element");
    }

    public function testFindReturnsHordeRdoList()
    {
        $result = self::$LazyBaseObjectMapper->find();
        $this->assertTrue($result instanceof Horde_Rdo_List, "find() returns a Horde_Rdo_List");
    }

    public function testFindOneReturnsEntity()
    {
        $result = self::$LazyBaseObjectMapper->findOne();
        $this->assertTrue($result instanceof Horde_Rdo_Base, "findOne() returns a Horde_Rdo_Base");
    }
    public function testFindOneWithScalarReturnsEntityWithKeyValue()
    {
        $result = self::$LazyBaseObjectMapper->findOne(2);
        $this->assertEquals(2, $result->baseobject_id, "findOne() returns the right Horde_Rdo_Base if key is given as argument");
    }

    public function testToOneRelationRetrievesEntityWhenKeyIsFound()
    {
        $entity = self::$LazyBaseObjectMapper->findOne(1);
        $this->assertTrue($entity->lazyRelatedThing instanceof Horde_Rdo_Test_Objects_RelatedThing, "to-one-relations return an instance object");
    }

    public function testToOneRelationRetrievesCorrectEntityWhenKeyIsFound()
    {
        $result = self::$LazyBaseObjectMapper->findOne(1);
        $this->assertEquals(100, $result->lazyRelatedThing->relatedthing_intproperty, "to-one-relations return correct related object when key is found");
    }
   /**
    * @expectedException Horde_Rdo_Exception
    */
    public function testLazyToOneRelationThrowsExceptionWhenKeyIsNotFound()
    {
        $entity = self::$LazyBaseObjectMapper->findOne(3);
        $this->assertNull($entity->lazyRelatedThing, "lazy to-one-relations throw exception when relation key is not found");
    }

    public function testLazyToOneRelationReturnsNullWhenKeyIsEmpty()
    {
        $entity = self::$LazyBaseObjectMapper->findOne(4);
        $this->assertNull($entity->lazyRelatedThing, "lazy to-one-relations returns 0 when relation key is empty() value");
    }

    public function testObjectWithEagerToOneRelationIsNotLoadedWhenRelatedObjectDoesntExist()
    {
        $entity = self::$EagerBaseObjectMapper->findOne(3);
        $this->assertNull($entity, "Base Object not loaded when eager relation key references nonexisting line");
    }

    public function testObjectWithEagerToOneRelationIsNotLoadedWhenlWhenKeyIsNull()
    {
        $entity = self::$EagerBaseObjectMapper->findOne(4);
        $this->assertNull($entity, "Base Object not loaded when eager relation key is null");
    }


    public static function tearDownAfterClass()
    {
        if (self::$db) {
            $migration = new Horde_Db_Migration_Base(self::$db);
            $migration->dropTable('test_someeagerbaseobjects');
            $migration->dropTable('test_somelazybaseobjects');
            $migration->dropTable('test_relatedthings');
            $migration->dropTable('test_manytomanya');
            $migration->dropTable('test_manytomanyb');
            $migration->dropTable('test_manythrough');
            self::$db->disconnect();
            self::$db = null;
        }
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped('No sqlite extension or no sqlite PDO driver.');
        }

       self::$LazyBaseObjectMapper = new Horde_Rdo_Test_Objects_SomeLazyBaseObjectMapper(self::$db);
       self::$EagerBaseObjectMapper = new Horde_Rdo_Test_Objects_SomeEagerBaseObjectMapper(self::$db);
       self::$MtmaMapper = new Horde_Rdo_Test_Objects_ManyToManyAMapper(self::$db);
       self::$MtmbMapper = new Horde_Rdo_Test_Objects_ManyToManyBMapper(self::$db);
    }
}
