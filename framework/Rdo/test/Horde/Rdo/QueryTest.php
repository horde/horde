<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Rdo
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Rdo_QueryTest extends Horde_Test_Case
{
    protected $db;
    protected $mapper;

    public function setUp()
    {
        $factory_db = new Horde_Test_Factory_Db();
        $this->db = $factory_db->create();
        $this->mapper = new Horde_Rdo_Test_Objects_SimpleMapper($this->db);
        $migration = new Horde_Db_Migration_Base($this->db);
        try {
            $migration->dropTable('horde_rdo_test');
        } catch (Horde_Db_Exception $e) {
        }
        $t = $migration->createTable(
            'horde_rdo_test', array('autoincrementKey' => 'id')
        );
        $t->column('intprop', 'integer');
        $t->column('textprop', 'string');
        $t->end();
        $migration->migrate('up');
    }

    public function testConstructor()
    {
        $query = new Horde_Rdo_Query();
        $this->assertNull($query->mapper);
        $query = new Horde_Rdo_Query($this->mapper);
        $this->assertSame($this->mapper, $query->mapper);
    }

    public function testCreate()
    {
        $query1 = new Horde_Rdo_Query();
        $query2 = Horde_Rdo_Query::create($query1);
        $this->assertInstanceOf('Horde_Rdo_Query', $query2);
        $this->assertNotSame($query1, $query2);

        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $this->assertInstanceOf('Horde_Rdo_Query', $query);
        $this->assertEquals(
            array(
                array(
                    'field' => $this->mapper->tableDefinition->getPrimaryKey(),
                    'test' => '=',
                    'value' => 4
                ),
            ),
            $query->tests
        );

        $query = Horde_Rdo_Query::create(
            array('textprop' => 'bar', 'intprop' => 2),
            $this->mapper
        );
        $this->assertInstanceOf('Horde_Rdo_Query', $query);
        $this->assertEquals(
            array(
                array('field' => 'textprop', 'test' => '=', 'value' => 'bar'),
                array('field' => 'intprop', 'test' => '=', 'value' => 2),
            ),
            $query->tests
        );
        $this->assertEquals(
            array(
                'horde_rdo_test.id',
                'horde_rdo_test.intprop',
                'horde_rdo_test.textprop'
            ),
            $query->fields
        );
        $this->assertEquals('AND', $query->conjunction);
    }

    public function testGetQuery()
    {
        $query = Horde_Rdo_Query::create(
            array('textprop' => 'bar', 'intprop' => 2),
            $this->mapper
        );
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."textprop" = ? AND horde_rdo_test."intprop" = ?',
                array('bar', 2)
            ),
            $query->getQuery()
        );
    }

    public function testDistinct()
    {
        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $query->distinct(true);
        $this->assertEquals(
            array(
                'SELECT DISTINCT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ?',
                array(4)
            ),
            $query->getQuery()
        );
    }

    public function testSetFields()
    {
        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $query1 = $query->setFields(array('intprop'));
        $this->assertSame($query, $query1);
        $this->assertEquals(
            array(
                'SELECT intprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ?',
                array(4)
            ),
            $query->getQuery()
        );

        $query->setFields(array('intprop'), 'prefix.');
        $this->assertEquals(
            array(
                'SELECT prefix.intprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ?',
                array(4)
            ),
            $query->getQuery()
        );
    }

    public function testAddFields()
    {
        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $query->setFields(array('intprop'));
        $query->addFields(array('id'));
        $this->assertEquals(
            array(
                'SELECT intprop, id FROM horde_rdo_test WHERE horde_rdo_test."id" = ?',
                array(4)
            ),
            $query->getQuery()
        );

        $query->addFields(array('textprop'), 'prefix.');
        $this->assertEquals(
            array(
                'SELECT intprop, id, prefix.textprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ?',
                array(4)
            ),
            $query->getQuery()
        );
    }

    public function testCombineWith()
    {
        $query = Horde_Rdo_Query::create(
            array('textprop' => 'bar', 'intprop' => 2),
            $this->mapper
        );
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."textprop" = ? AND horde_rdo_test."intprop" = ?',
                array('bar', 2)
            ),
            $query->getQuery()
        );
        $query->combineWith('OR');
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."textprop" = ? OR horde_rdo_test."intprop" = ?',
                array('bar', 2)
            ),
            $query->getQuery()
        );
    }

    public function testSortBy()
    {
        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $query->sortBy('intprop');
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ? ORDER BY intprop',
                array(4)
            ),
            $query->getQuery()
        );
        $query->sortBy('textprop');
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ? ORDER BY intprop, textprop',
                array(4)
            ),
            $query->getQuery()
        );
    }

    public function testSortByProperty()
    {
        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $query->sortBy('intprop');
        $this->assertEquals(array('intprop'), $query->sortby);

        $this->mapper->sortBy('textprop');
        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $this->assertEquals(array('textprop'), $query->sortby);
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ? ORDER BY textprop',
                array(4)
            ),
            $query->getQuery()
        );
    }

    public function testClearSort()
    {
        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $query->sortBy('intprop');
        $query->clearSort();
        $query->sortBy('textprop');
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ? ORDER BY textprop',
                array(4)
            ),
            $query->getQuery()
        );
    }

    public function testLimit()
    {
        $query = Horde_Rdo_Query::create(4, $this->mapper);
        $query->limit(10);
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ? LIMIT 10',
                array(4)
            ),
            $query->getQuery()
        );
        $query->limit(10, 20);
        $this->assertEquals(
            array(
                'SELECT horde_rdo_test.id, horde_rdo_test.intprop, horde_rdo_test.textprop FROM horde_rdo_test WHERE horde_rdo_test."id" = ? LIMIT 20, 10',
                array(4)
            ),
            $query->getQuery()
        );
    }
}
