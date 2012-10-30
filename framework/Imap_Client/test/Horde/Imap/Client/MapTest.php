<?php
/**
 * Tests for the UID -> Sequence Number mapping object.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * Tests for the UID -> Sequence Number mapping object.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
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

    public function testLookup()
    {
        $map = clone $this->map;

        $this->assertEquals(
            array(
                2 => 5,
                4 => 10,
                6 => 15
            ),
            $map->lookup(new Horde_Imap_Client_Ids('5:15'))
        );

        $this->assertEquals(
            array(
                2 => 5,
                4 => 10,
                6 => 15
            ),
            $map->lookup(new Horde_Imap_Client_Ids('2:6', true))
        );

        $this->assertEquals(
            $map->map,
            $map->lookup(new Horde_Imap_Client_Ids(Horde_Imap_Client_Ids::ALL))
        );
    }

    public function testRemove()
    {
        $map = clone $this->map;
        $map->remove(new Horde_Imap_Client_Ids('10'));

        $this->assertEquals(
            array(
                2 => 5,
                5 => 15,
                7 => 20,
                9 => 25,
                11 => 30
            ),
            $map->map
        );

        $map = clone $this->map;
        $map->remove(new Horde_Imap_Client_Ids('4', true));

        $this->assertEquals(
            array(
                2 => 5,
                5 => 15,
                7 => 20,
                9 => 25,
                11 => 30
            ),
            $map->map
        );

        $map = clone $this->map;
        $map->remove(new Horde_Imap_Client_Ids('10:15,25'));

        $this->assertEquals(
            array(
                2 => 5,
                6 => 20,
                9 => 30
            ),
            $map->map
        );

        // Efficient sequence number remove.
        $map = clone $this->map;
        $map->remove(new Horde_Imap_Client_Ids(array('10', '6', '4'), true));

        $this->assertEquals(
            array(
                2 => 5,
                6 => 20,
                9 => 30
            ),
            $map->map
        );

        // Inefficient sequence number remove.
        $map = clone $this->map;
        $map->remove(new Horde_Imap_Client_Ids(array('4', '5', '8'), true));

        $this->assertEquals(
            array(
                2 => 5,
                6 => 20,
                9 => 30
            ),
            $map->map
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

    public function testCount()
    {
        $this->assertEquals(
            6,
            count($this->map)
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
