<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2012-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Related class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2012-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_RelatedTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider startProvider
     */
    public function testStart($ob, $id)
    {
        $this->assertEquals(
            $id,
            $ob->startId()
        );
    }

    public function startProvider()
    {
        return array(
            array(
                new Horde_Mime_Related(Horde_Mime_Part::parseMessage(
                    file_get_contents(__DIR__ . '/fixtures/related_msg.txt')
                )),
                1
            ),
            array(
                new Horde_Mime_Related(Horde_Mime_Part::parseMessage(
                    file_get_contents(__DIR__ . '/fixtures/related_msg_2.txt')
                )),
                2
            )
        );
    }

    /**
     * @dataProvider searchProvider
     */
    public function testSearch($ob, $search, $id)
    {
        $this->assertEquals(
            $id,
            $ob->cidSearch($search)
        );
    }

    public function searchProvider()
    {
        return array(
            array(
                new Horde_Mime_Related(Horde_Mime_Part::parseMessage(
                    file_get_contents(__DIR__ . '/fixtures/related_msg.txt')
                )),
                '789',
                3
            ),
            array(
                new Horde_Mime_Related(Horde_Mime_Part::parseMessage(
                    file_get_contents(__DIR__ . '/fixtures/related_msg_2.txt')
                )),
                'abc',
                2
            )
        );
    }

    /**
     * @dataProvider iteratorProvider
     */
    public function testIterator($ob, $ids)
    {
        $this->assertEquals(
            $ids,
            iterator_to_array($ob)
        );
    }

    public function iteratorProvider()
    {
        return array(
            array(
                new Horde_Mime_Related(Horde_Mime_Part::parseMessage(
                    file_get_contents(__DIR__ . '/fixtures/related_msg.txt')
                )),
                array('2' => '456', '3' => '789')
            ),
            array(
                new Horde_Mime_Related(Horde_Mime_Part::parseMessage(
                    file_get_contents(__DIR__ . '/fixtures/related_msg_2.txt')
                )),
                array('2' => 'abc')
            )
        );
    }

    public function testReplace()
    {
        $part = Horde_Mime_Part::parseMessage(
            file_get_contents(__DIR__ . '/fixtures/related_msg.txt')
        );
        $related = new Horde_Mime_Related($part);

        $ob = $related->cidReplace(
            $part['1']->getContents(),
            array($this, 'callbackTestReplace')
        );

        $body = $ob->dom->getElementsByTagName('body');
        $this->assertEquals(
            1,
            $body->length
        );
        $this->assertEquals(
            '2',
            $body->item(0)->getAttribute('background')
        );

        $body = $ob->dom->getElementsByTagName('img');
        $this->assertEquals(
            1,
            $body->length
        );
        $this->assertEquals(
            '3',
            $body->item(0)->getAttribute('src')
        );
    }

    public function callbackTestReplace($id)
    {
        return $id;
    }

}
