<?php
/**
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
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
 * Tests for the Ids object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_IdsTest extends PHPUnit_Framework_TestCase
{
    public function testBasicAddingOfIds()
    {
        $ids = new Horde_Imap_Client_Ids(array(1, 3, 5));

        $this->assertEquals(
            3,
            count($ids)
        );

        $this->assertEquals(
            '1,3,5',
            strval($ids)
        );

        $this->assertFalse($ids->isEmpty());
    }

    /**
     * @dataProvider ignoreInvalidAddsProvider
     */
    public function testIgnoreInvalidAdds($in)
    {
        $ids = new Horde_Imap_Client_Ids($in);
        $this->assertEquals(
            0,
            count($ids)
        );

        $ids = new Horde_Imap_Client_Ids();
        $ids->add($in);
        $this->assertEquals(
            0,
            count($ids)
        );
    }

    public function ignoreInvalidAddsProvider()
    {
        return array(
            array(null),
            array(new stdClass)
        );
    }

    public function testEmptyIdsArray()
    {
        $ids = new Horde_Imap_Client_Ids(array());

        $this->assertEquals(
            0,
            count($ids)
        );

        $this->assertEquals(
            '',
            strval($ids)
        );

        $this->assertTrue($ids->isEmpty());
    }

    /**
     * @dataProvider sequenceParsingProvider
     */
    public function testSequenceParsing($in, $expected)
    {
        $ids = new Horde_Imap_Client_Ids($in);

        $this->assertEquals(
            count($expected),
            count($ids)
        );

        $this->assertEquals(
            $expected,
            iterator_to_array($ids)
        );
    }

    public function sequenceParsingProvider()
    {
        return array(
            array('12:10', array(10, 11, 12)),
            array('12,11,10', array(12, 11, 10)),
            array('10:12,10,11,12,10:12', array(10, 11, 12)),
            array('10', array(10))
        );
    }

    /**
     * @dataProvider rangeGenerationProvider
     */
    public function testRangeGeneration($in, $expected)
    {
        $ids = new Horde_Imap_Client_Ids($in);

        $this->assertEquals(
            $expected,
            $ids->range_string
        );
    }

    public function rangeGenerationProvider()
    {
        return array(
            array('100,300,200', '100:300'),
            array(Horde_Imap_Client_Ids::ALL, ''),
            array('50', '50')
        );
    }

    public function testSorting()
    {
        $ids = new Horde_Imap_Client_Ids('14,12,10');

        $this->assertEquals(
            '14,12,10',
            $ids->tostring
        );
        $this->assertEquals(
            '10,12,14',
            $ids->tostring_sort
        );
    }

    /**
     * @dataProvider specialIdValueStringRepresentationsProvider
     */
    public function testSpecialIdValueStringRepresentations($in, $expected)
    {
        $ids = new Horde_Imap_Client_Ids($in);

        $this->assertEquals(
            $expected,
            $ids->tostring
        );
    }

    public function specialIdValueStringRepresentationsProvider()
    {
        return array(
            array(Horde_Imap_Client_Ids::ALL, '1:*'),
            array(Horde_Imap_Client_Ids::SEARCH_RES, '$'),
            array(Horde_Imap_Client_Ids::LARGEST, '*')
        );
    }

    public function testDuplicatesAllowed()
    {
        $ids = new Horde_Imap_Client_Ids('1:10,1:10');
        $this->assertEquals(
            10,
            count($ids)
        );

        $ids = new Horde_Imap_Client_Ids();
        $ids->duplicates = true;
        $ids->add('1:10,1:10');
        $this->assertEquals(
            20,
            count($ids)
        );
    }

    public function testSplit()
    {
        // ~5000 non-sequential IDs
        $ids = new Horde_Imap_Client_Ids(range(1, 10000, 2));

        $split = $ids->split(2000);

        $this->assertGreaterThan(1, count($split));

        foreach (array_slice($split, 0, -1) as $val) {
            $this->assertGreaterThan(
                2000,
                strlen($val)
            );

            $this->assertNotEquals(
                ',',
                substr($val, -1)
            );
        }

        $last = array_pop($split);
        $this->assertLessThan(
            2001,
            strlen($last)
        );

        $this->assertNotEquals(
            ',',
            substr($last, -1)
        );
    }

    public function testSplitOnAll()
    {
        $ids = new Horde_Imap_Client_Ids(Horde_Imap_Client_Ids::ALL);

        $this->assertEquals(
            array(
                '1:*'
            ),
            $ids->split(2000)
        );
    }

    public function testRemove()
    {
        $ids = new Horde_Imap_Client_Ids('1:100');
        $this->assertEquals(
            100,
            count($ids)
        );

        // Remove from array
        $ids->remove(range(1, 10));
        $this->assertEquals(
            90,
            count($ids)
        );

        // Removing same IDs shouldn't change anything.
        $ids->remove(range(1, 10));
        $this->assertEquals(
            90,
            count($ids)
        );

        // Remove via sequence string
        $ids->remove('11:20');
        $this->assertEquals(
            80,
            count($ids)
        );

        // Remove via object
        $ids->remove(new Horde_Imap_Client_Ids('21:30'));
        $this->assertEquals(
            70,
            count($ids)
        );
    }

    /**
     * @dataProvider minAndMaxProvider
     */
    public function testMinAndMax($in, $min, $max)
    {
        $ids = new Horde_Imap_Client_Ids($in);

        $this->assertEquals(
            $min,
            $ids->min
        );
        $this->assertEquals(
            $max,
            $ids->max
        );
    }

    public function minAndMaxProvider()
    {
        return array(
            array(array(1), 1, 1),
            array(array(1, 2), 1, 2),
            array(array(1, 5, 3), 1, 5)
        );
    }

    public function testReverse()
    {
        $ids = new Horde_Imap_Client_Ids(array(1, 3, 5));
        $ids->reverse();

        $this->assertEquals(
            array(5, 3, 1),
            $ids->ids
        );
    }

    /**
     * @dataProvider sequenceStringGenerationProvider
     */
    public function testSequenceStringGeneration($in, $expected)
    {
        $ids = new Horde_Imap_Client_Ids($in);

        $this->assertEquals(
            $expected,
            strval($ids)
        );
    }

    public function sequenceStringGenerationProvider()
    {
        return array(
            array(array(1, 2, 3), '1:3'),
            array(array(3, 2, 1), '3,2,1'),
            array(array(1, 2, 3, 5), '1:3,5')
        );
    }

    public function testClone()
    {
        $ids = new Horde_Imap_Client_Ids(array(1, 3));

        $ids2 = clone $ids;
        $ids2->add(5);

        $this->assertEquals(
            array(1, 3),
            iterator_to_array($ids)
        );
        $this->assertEquals(
            array(1, 3, 5),
            iterator_to_array($ids2)
        );
    }

    public function testSerialize()
    {
        $ids = new Horde_Imap_Client_Ids(array(1, 3, 5));

        $ids2 = unserialize(serialize($ids));

        $this->assertEquals(
            array(1, 3, 5),
            iterator_to_array($ids2)
        );
    }

}
