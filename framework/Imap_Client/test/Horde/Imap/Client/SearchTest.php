<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Search Query object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_SearchTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider flagQueryProvider
     */
    public function testFlagQuery($flags, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();

        foreach ($flags as $val) {
            $ob->flag($val[0], $val[1], array('fuzzy' => $fuzzy));
        }

        $this->assertTrue($ob->flagSearch());
        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob) : strval($ob)
        );
    }

    public function flagQueryProvider()
    {
        return array(
            array(
                array(
                    /* System flag - set. */
                    array('\\answered', true),
                    /* System flag - not set. */
                    array('\\draft', false),
                    /* System flag - set. */
                    array('foo', true),
                    /* System flag - not set. */
                    array('bar', false)
                ),
                false,
                'ANSWERED UNDRAFT KEYWORD FOO UNKEYWORD BAR'
            ),
            array(
                array(
                    array('foo', true)
                ),
                true,
                'FUZZY KEYWORD FOO'
            )
        );
    }

    /**
     * @dataProvider newMsgsQueryProvider
     */
    public function testNewMsgsQuery($newmsgs, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->newMsgs($newmsgs, array('fuzzy' => $fuzzy));

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob) : strval($ob)
        );
    }

    public function newMsgsQueryProvider()
    {
        return array(
            array(true, false, 'NEW'),
            array(false, false, 'OLD'),
            array(true, true, 'FUZZY NEW'),
            array(false, true, 'FUZZY OLD')
        );
    }

    /**
     * @dataProvider headerTextQueryProvider
     */
    public function testHeaderTextQuery($not, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->headerText('Foo', 'Bar', $not, array('fuzzy' => $fuzzy));

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob) : strval($ob)
        );
    }

    public function headerTextQueryProvider()
    {
        return array(
            array(false, false, 'HEADER FOO Bar'),
            array(true, false, 'NOT HEADER FOO Bar'),
            array(false, true, 'FUZZY HEADER FOO Bar'),
            array(true, true, 'FUZZY NOT HEADER FOO Bar')
        );
    }

    public function testHeaderTextUtf8Query()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->headerText('Foo', 'EëE');

        try {
            $ob->build();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {
            // Expected
        }

        $ob->charset('UTF-8', false);

        $this->assertNotEmpty($ob->build());
    }

    /**
     * @dataProvider textQueryProvider
     */
    public function testTextQuery($body, $not, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->text('foo', $body, $not, array('fuzzy' => $fuzzy));

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob) : strval($ob)
        );
    }

    public function textQueryProvider()
    {
        return array(
            array(true, false, false, 'BODY foo'),
            array(false, false, false, 'TEXT foo'),
            array(true, true, false, 'NOT BODY foo'),
            array(false, true, false, 'NOT TEXT foo'),
            array(true, false, true, 'FUZZY BODY foo'),
            array(false, false, true, 'FUZZY TEXT foo'),
            array(true, true, true, 'FUZZY NOT BODY foo'),
            array(false, true, true, 'FUZZY NOT TEXT foo')
        );
    }

    /**
     * @dataProvider sizeQueryProvider
     */
    public function testSizeQuery($larger, $not, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->size(100, $larger, $not, array('fuzzy' => $fuzzy));

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob) : strval($ob)
        );
    }

    public function sizeQueryProvider()
    {
        return array(
            array(true, false, false, 'LARGER 100'),
            array(false, false, false, 'SMALLER 100'),
            array(true, true, false, 'NOT LARGER 100'),
            array(false, true, false, 'NOT SMALLER 100'),
            array(true, false, true, 'FUZZY LARGER 100'),
            array(false, false, true, 'FUZZY SMALLER 100'),
            array(true, true, true, 'FUZZY NOT LARGER 100'),
            array(false, true, true, 'FUZZY NOT SMALLER 100')
        );
    }

    /**
     * @dataProvider idsQueryProvider
     */
    public function testIdsQuery($ids, $not, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->ids(new Horde_Imap_Client_Ids($ids), $not, array(
            'fuzzy' => $fuzzy
        ));

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob) : strval($ob)
        );
    }

    public function idsQueryProvider()
    {
        return array(
            array('1,2,3', false, false, 'UID 1:3'),
            array('1:3', true, false, 'NOT UID 1:3'),
            array('1,2,3', false, true, 'FUZZY UID 1:3'),
            array('1:3', true, true, 'FUZZY NOT UID 1:3')
        );
    }

    /**
     * @dataProvider dateSearchQueryProvider
     */
    public function testDateSearchQuery(
        $range, $header, $not, $fuzzy, $expected
    )
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->dateSearch(
            new DateTime('January 1, 2010'),
            $range,
            $header,
            $not,
            array('fuzzy' => $fuzzy)
        );

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob) : strval($ob)
        );
    }

    public function dateSearchQueryProvider()
    {
        return array(
            array(
                Horde_Imap_Client_Search_Query::DATE_BEFORE,
                true,
                false,
                false,
                'SENTBEFORE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_BEFORE,
                false,
                false,
                false,
                'BEFORE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_BEFORE,
                true,
                true,
                false,
                'NOT SENTBEFORE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_BEFORE,
                false,
                true,
                false,
                'NOT BEFORE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_BEFORE,
                true,
                true,
                true,
                'FUZZY NOT SENTBEFORE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_BEFORE,
                false,
                true,
                true,
                'FUZZY NOT BEFORE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_ON,
                true,
                false,
                false,
                'SENTON 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_ON,
                false,
                false,
                false,
                'ON 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_ON,
                true,
                true,
                false,
                'NOT SENTON 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_ON,
                false,
                true,
                false,
                'NOT ON 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_ON,
                true,
                true,
                true,
                'FUZZY NOT SENTON 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_ON,
                false,
                true,
                true,
                'FUZZY NOT ON 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_SINCE,
                true,
                false,
                false,
                'SENTSINCE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_SINCE,
                false,
                false,
                false,
                'SINCE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_SINCE,
                true,
                true,
                false,
                'NOT SENTSINCE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_SINCE,
                false,
                true,
                false,
                'NOT SINCE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_SINCE,
                true,
                true,
                true,
                'FUZZY NOT SENTSINCE 1-Jan-2010',
            ),
            array(
                Horde_Imap_Client_Search_Query::DATE_SINCE,
                false,
                true,
                true,
                'FUZZY NOT SINCE 1-Jan-2010',
            )
        );
    }

    /**
     * @dataProvider intervalSearchQueryProvider
     */
    public function testIntervalSearchQuery($range, $not, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->intervalSearch(30, $range, $not, array('fuzzy' => $fuzzy));

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob, array('WITHIN')) : strval($ob)
        );
    }

    public function intervalSearchQueryProvider()
    {
        return array(
            array(
                Horde_Imap_Client_Search_Query::INTERVAL_OLDER,
                false,
                false,
                'OLDER 30'
            ),
            array(
                Horde_Imap_Client_Search_Query::INTERVAL_OLDER,
                true,
                false,
                'NOT OLDER 30'
            ),
            array(
                Horde_Imap_Client_Search_Query::INTERVAL_OLDER,
                false,
                true,
                'FUZZY OLDER 30'
            ),
            array(
                Horde_Imap_Client_Search_Query::INTERVAL_OLDER,
                true,
                true,
                'FUZZY NOT OLDER 30'
            ),
            array(
                Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER,
                false,
                false,
                'YOUNGER 30'
            ),
            array(
                Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER,
                true,
                false,
                'NOT YOUNGER 30'
            ),
            array(
                Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER,
                false,
                true,
                'FUZZY YOUNGER 30'
            ),
            array(
                Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER,
                true,
                true,
                'FUZZY NOT YOUNGER 30'
            )
        );
    }

    public function testOrQueries()
    {
        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->flag('\\deleted', false);
        $ob2->headerText('from', 'ABC');

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->flag('\\deleted', true);
        $ob3->headerText('from', 'DEF');

        $ob = new Horde_Imap_Client_Search_Query();
        $ob->orSearch(array($ob2, $ob3));

        $this->assertEquals(
            'OR (DELETED FROM DEF) (UNDELETED FROM ABC)',
            strval($ob)
        );

        $ob4 = new Horde_Imap_Client_Search_Query();
        $ob4->flag('\\flagged', true);
        $ob4->headerText('from', 'GHI');

        $ob5 = new Horde_Imap_Client_Search_Query();
        $ob5->orSearch(array($ob2, $ob3, $ob4));

        $this->assertEquals(
            'OR (FLAGGED FROM GHI) (OR (DELETED FROM DEF) (UNDELETED FROM ABC))',
            strval($ob5)
        );
    }

    public function testOrQueriesWithABaseQuery()
    {
        $or_ob = new Horde_Imap_Client_Search_Query();

        $ob = new Horde_Imap_Client_Search_Query();
        $ob->flag('\\deleted', false);
        $ob->headerText('from', 'ABC');
        $or_ob->orSearch($ob);

        $ob = new Horde_Imap_Client_Search_Query();
        $ob->flag('\\deleted', true);
        $ob->headerText('from', 'DEF');
        $or_ob->orSearch($ob);

        $base_ob = new Horde_Imap_Client_Search_Query();
        $base_ob->flag('\\seen', false);
        $base_ob->andSearch($or_ob);

        $this->assertEquals(
            'UNSEEN OR (DELETED FROM DEF) (UNDELETED FROM ABC)',
             strval($base_ob)
         );
    }

    /**
     * @dataProvider modseqSearchQueryProvider
     */
    public function testModseq($name, $type, $not, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->modseq(123, $name, $type, $not, array('fuzzy' => $fuzzy));

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob, array('CONDSTORE')) : strval($ob)
        );
    }

    public function modseqSearchQueryProvider()
    {
        return array(
            array(null, null, false, false, 'MODSEQ 123'),
            array(null, null, true, false, 'NOT MODSEQ 123'),
            array(null, null, false, true, 'FUZZY MODSEQ 123'),
            array(null, null, true, true, 'FUZZY NOT MODSEQ 123'),
            array('foo', null, false, false, 'MODSEQ "foo" all 123'),
            array('foo', null, true, false, 'NOT MODSEQ "foo" all 123'),
            array('foo', null, false, true, 'FUZZY MODSEQ "foo" all 123'),
            array('foo', null, true, true, 'FUZZY NOT MODSEQ "foo" all 123'),
            array('foo', 'all', false, false, 'MODSEQ "foo" all 123'),
            array('foo', 'all', true, false, 'NOT MODSEQ "foo" all 123'),
            array('foo', 'all', false, true, 'FUZZY MODSEQ "foo" all 123'),
            array('foo', 'all', true, true, 'FUZZY NOT MODSEQ "foo" all 123'),
            array('foo', 'shared', false, false, 'MODSEQ "foo" shared 123'),
            array('foo', 'shared', true, false, 'NOT MODSEQ "foo" shared 123'),
            array('foo', 'shared', false, true, 'FUZZY MODSEQ "foo" shared 123'),
            array('foo', 'shared', true, true, 'FUZZY NOT MODSEQ "foo" shared 123'),
            array('foo', 'priv', false, false, 'MODSEQ "foo" priv 123'),
            array('foo', 'priv', true, false, 'NOT MODSEQ "foo" priv 123'),
            array('foo', 'priv', false, true, 'FUZZY MODSEQ "foo" priv 123'),
            array('foo', 'priv', true, true, 'FUZZY NOT MODSEQ "foo" priv 123')
        );
    }

    /**
     * @dataProvider previousSearchQueryProvider
     */
    public function testPreviousSearchQuery($not, $fuzzy, $expected)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->previousSearch($not, array('fuzzy' => $fuzzy));

        $this->assertEquals(
            $expected,
            $fuzzy ? $this->_fuzzy($ob, array('ESEARCH', 'SEARCHRES')) : strval($ob)
        );
    }

    public function previousSearchQueryProvider()
    {
        return array(
            array(false, false, 'NOT $'),
            array(true, false, '$'),
            array(false, true, 'FUZZY NOT $'),
            array(true, true, 'FUZZY $')
        );
    }

    public function testSerialize()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->ids(new Horde_Imap_Client_Ids('1:3'), true);
        $ob->text('foo');
        $ob->charset('US-ASCII', false);

        $this->assertEquals(
            'BODY foo NOT UID 1:3',
            strval(unserialize(serialize($ob)))
        );
    }

    private function _fuzzy($ob, array $exts = array())
    {
        $capability = new Horde_Imap_Client_Data_Capability_Imap();
        $capability->add('SEARCH', 'FUZZY');
        foreach ($exts as $val) {
            $capability->add($val);
        }

        $res = $ob->build($capability);
        return $res['query']->escape();
    }

}
