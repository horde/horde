<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Imap_Client_Fetch_Results object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
abstract class Horde_Imap_Client_Fetch_Results_TestBase
extends PHPUnit_Framework_TestCase
{
    protected $ob;

    /* Set in child class via _setUp(). */
    protected $ob_class;
    protected $ob_ids;
    abstract protected function _setUp();

    public function setUp()
    {
        $this->_setUp();

        $this->ob = new Horde_Imap_Client_Fetch_Results($this->ob_class);
        foreach ($this->ob_ids as $val) {
            $this->ob[$val] = new $this->ob_class();
        }
    }

    /**
     * @dataProvider keyTypeProvider
     */
    public function testKeyType($ob_key_type, $key_type)
    {
        $reflection = new ReflectionClass($this->ob);
        $ob = $reflection->newInstanceArgs(array_filter(array(
            $this->ob_class,
            $ob_key_type
        )));

        $this->assertEquals(
            $key_type,
            $ob->key_type
        );
    }

    public function keyTypeProvider()
    {
        return array(
            array(
                Horde_Imap_Client_Fetch_Results::SEQUENCE,
                Horde_Imap_Client_Fetch_Results::SEQUENCE
            ),
            array(
                Horde_Imap_Client_Fetch_Results::UID,
                Horde_Imap_Client_Fetch_Results::UID
            ),
            array(
                null,
                Horde_Imap_Client_Fetch_Results::UID
            )
        );
    }

    public function testGet()
    {
        $ids = array_merge(
            $this->ob_ids,
            /* Create non-existent object. */
            array('1000', 'Z')
        );

        foreach ($ids as $id) {
            $this->assertInstanceOf(
                $this->ob_class,
                $this->ob->get($id)
            );
        }
    }

    public function testIds()
    {
        $this->assertEquals(
            $this->ob_ids,
            $this->ob->ids()
        );
    }

    public function testFirst()
    {
        $this->assertNull($this->ob->first());

        $ob = new Horde_Imap_Client_Fetch_Results($this->ob_class);
        $fetch = $ob->get('1');

        $this->assertEquals(
            $fetch,
            $ob->first()
        );
    }

    public function testClear()
    {
        $this->ob->clear();

        $this->assertEquals(
            array(),
            $this->ob->ids()
        );
    }

    public function testCount()
    {
        $this->assertEquals(
            count($this->ob_ids),
            count($this->ob)
        );
    }

    public function testIterator()
    {
        $i = 0;

        foreach ($this->ob as $val) {
            $this->assertInstanceof(
                $this->ob_class,
                $val
            );
            ++$i;
        }

        $this->assertEquals(
            count($this->ob_ids),
            $i
        );
    }

    public function testSerialize()
    {
        $this->ob->get('1')->setModSeq(500);

        $ob2 = unserialize(serialize($this->ob));

        $this->assertEquals(
            500,
            $ob2->get('1')->getModSeq()
        );
    }

}
