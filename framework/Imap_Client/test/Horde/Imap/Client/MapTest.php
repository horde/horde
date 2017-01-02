<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the UID -> Sequence Number mapping object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_MapTest extends PHPUnit_Framework_TestCase
{
    private $lookup;
    private $map;

    public function setUp()
    {
        $this->lookup = array(
            2 => 5,
            4 => 10,
            6 => 15,
            8 => 20,
            10 => 25,
            12 => 30
        );

        $this->map = new Horde_Imap_Client_Ids_Map($this->lookup);
    }

    public function testUpdate()
    {
        $map = new Horde_Imap_Client_Ids_Map();
        $map->update(array(
            1 => 1
        ));

        $this->assertEquals(
            array(
                1 => 1
            ),
            $map->map
        );

        $map->update(array(
            2 => 2
        ));

        $this->assertEquals(
            array(
                1 => 1,
                2 => 2
            ),
            $map->map
        );

        $map->update(array(
            1 => 3
        ));

        $this->assertEquals(
            array(
                2 => 2,
                1 => 3
            ),
            $map->map
        );

        $map->update(array(
            2 => 4,
            1 => 5
        ));

        $this->assertEquals(
            array(
                2 => 4,
                1 => 5
            ),
            $map->map
        );
    }

    public function testCount()
    {
        $this->assertEquals(
            6,
            count($this->map)
        );
    }

    /**
     * @depends testCount
     */
    public function testClone()
    {
        $map2 = clone $this->map;
        $map2->update(array(
            1 => 1
        ));

        $this->assertEquals(
            6,
            count($this->map)
        );
        $this->assertEquals(
            7,
            count($map2)
        );
    }

    /**
     * @dataProvider lookupProvider
     * @depends testClone
     */
    public function testLookup($range, $expected = null)
    {
        $map = clone $this->map;

        $this->assertEquals(
            $expected ?: $map->map,
            $map->lookup($range)
        );
    }

    public function lookupProvider()
    {
        return array(
            array(
                new Horde_Imap_Client_Ids('5:15'),
                array(
                    2 => 5,
                    4 => 10,
                    6 => 15
                )
            ),
            array(
                new Horde_Imap_Client_Ids('2:6', true),
                array(
                    2 => 5,
                    4 => 10,
                    6 => 15
                )
            ),
            array(
                new Horde_Imap_Client_Ids(Horde_Imap_Client_Ids::ALL)
            )
        );
    }

    /**
     * @dataProvider removeProvider
     * @depends testClone
     */
    public function testRemove($range, $expected)
    {
        $map = clone $this->map;
        $map->remove($range);

        $this->assertEquals(
            $expected,
            $map->map
        );
    }

    public function removeProvider()
    {
        return array(
            array(
                new Horde_Imap_Client_Ids('10'),
                array(
                    2 => 5,
                    5 => 15,
                    7 => 20,
                    9 => 25,
                    11 => 30
                )
            ),
            array(
                new Horde_Imap_Client_Ids('4', true),
                array(
                    2 => 5,
                    5 => 15,
                    7 => 20,
                    9 => 25,
                    11 => 30
                )
            ),
            array(
                new Horde_Imap_Client_Ids('10:15,25'),
                array(
                    2 => 5,
                    6 => 20,
                    9 => 30
                )
            ),
            // Efficient sequence number remove.
            array(
                new Horde_Imap_Client_Ids(array('10', '6', '4'), true),
                array(
                    2 => 5,
                    6 => 20,
                    9 => 30
                )
            ),
            // Inefficient sequence number remove.
            array(
                new Horde_Imap_Client_Ids(array('4', '5', '8'), true),
                array(
                    2 => 5,
                    6 => 20,
                    9 => 30
                )
            ),
            // Shortcut removing all.
            array(
                new Horde_Imap_Client_Ids('5:30'),
                array()
            ),
            array(
                new Horde_Imap_Client_Ids(array('5', '10', '15', '20', '25', '30')),
                array()
            ),
            array(
                new Horde_Imap_Client_Ids(array('2', '4', '6', '8', '10', '12'), true),
                array()
            ),
            array(
                new Horde_Imap_Client_Ids(array('12', '10', '8', '6', '4', '2'), true),
                array()
            ),
        );
    }

    public function testRemoveWithDuplicateSequenceNumbers()
    {
        $map = new Horde_Imap_Client_Ids_Map(array(
            1 => 1,
            2 => 2,
            3 => 3
        ));

        // Inefficient sequence number remove with duplicate sequence numbers.
        $ids = new Horde_Imap_Client_Ids(array(), true);
        $ids->duplicates = true;
        $ids->add(array('2', '2'));

        $map->remove($ids);

        $this->assertEquals(
            array(
                1 => 1
            ),
            $map->map
        );
    }

    public function testIterator()
    {
        $this->assertEquals(
            $this->map->map,
            iterator_to_array($this->map)
        );
    }

    public function testSerialize()
    {
        $map = unserialize(serialize($this->map));

        $this->assertEquals(
            $this->map->map,
            $map->map
        );
    }

}
