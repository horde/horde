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
    public function testFlagQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();

        /* System flag - set. */
        $ob->flag('\\answered', true);
        /* System flag - not set. */
        $ob->flag('\\draft', false);
        /* System flag - set. */
        $ob->flag('foo', true);
        /* System flag - not set. */
        $ob->flag('bar', false);

        $this->assertEquals(
            'ANSWERED UNDRAFT KEYWORD FOO UNKEYWORD BAR',
            strval($ob)
        );

        $this->assertTrue($ob->flagSearch());

        /* Test fuzzy. */
        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->flag('foo', true, array('fuzzy' => true));

        $this->assertEquals(
            'FUZZY KEYWORD FOO',
            $this->_fuzzy($ob2)
        );
    }

    public function testNewMsgsQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->newMsgs();

        $this->assertEquals(
            'NEW',
            strval($ob)
        );

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->newMsgs(false);

        $this->assertEquals(
            'OLD',
            strval($ob2)
        );

        /* Test fuzzy. */
        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->newMsgs(true, array('fuzzy' => true));

        $this->assertEquals(
            'FUZZY NEW',
            $this->_fuzzy($ob3)
        );
    }

    public function testHeaderTextQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->headerText('Foo', 'Bar');

        $this->assertEquals(
            'HEADER FOO Bar',
            strval($ob)
        );

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->headerText('Foo', 'Bar', true);

        $this->assertEquals(
            'NOT HEADER FOO Bar',
            strval($ob2)
        );

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->headerText('Foo', 'Bar', true, array('fuzzy' => true));

        $this->assertEquals(
            'FUZZY NOT HEADER FOO Bar',
            $this->_fuzzy($ob3)
        );
    }

    public function testTextQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->text('foo');

        $this->assertEquals(
            'BODY foo',
            strval($ob)
        );

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->text('foo', false);

        $this->assertEquals(
            'TEXT foo',
            strval($ob2)
        );

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->text('foo', true, true);

        $this->assertEquals(
            'NOT BODY foo',
            strval($ob3)
        );

        $ob4 = new Horde_Imap_Client_Search_Query();
        $ob4->text('foo', false, true);

        $this->assertEquals(
            'NOT TEXT foo',
            strval($ob4)
        );

        $ob5 = new Horde_Imap_Client_Search_Query();
        $ob5->text('foo', false, true, array('fuzzy' => true));

        $this->assertEquals(
            'FUZZY NOT TEXT foo',
            $this->_fuzzy($ob5)
        );
    }

    public function testSizeQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->size(100);

        $this->assertEquals(
            'SMALLER 100',
            strval($ob)
        );

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->size(100, true);

        $this->assertEquals(
            'LARGER 100',
            strval($ob2)
        );

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->size(100, false, true);

        $this->assertEquals(
            'NOT SMALLER 100',
            strval($ob3)
        );

        $ob4 = new Horde_Imap_Client_Search_Query();
        $ob4->size(100, true, true);

        $this->assertEquals(
            'NOT LARGER 100',
            strval($ob4)
        );

        $ob5 = new Horde_Imap_Client_Search_Query();
        $ob5->size(100, false, true, array('fuzzy' => true));

        $this->assertEquals(
            'FUZZY NOT SMALLER 100',
            $this->_fuzzy($ob5)
        );
    }

    public function testIdsQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->ids(new Horde_Imap_Client_Ids('1,2,3'));

        $this->assertEquals(
            'UID 1:3',
            strval($ob)
        );

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->ids(new Horde_Imap_Client_Ids('1:3'), true);

        $this->assertEquals(
            'NOT UID 1:3',
            strval($ob2)
        );

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->ids(new Horde_Imap_Client_Ids('1:3'), true, array(
            'fuzzy' => true
        ));

        $this->assertEquals(
            'FUZZY NOT UID 1:3',
            $this->_fuzzy($ob3)
        );
    }

    public function testDateSearchQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->dateSearch(
            new DateTime('January 1, 2010'),
            $ob::DATE_ON
        );

        $this->assertEquals(
            'SENTON 1-Jan-2010',
            strval($ob)
        );
    }

    public function testIntervalSearchQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->intervalSearch(30, $ob::INTERVAL_OLDER);

        $this->assertEquals(
            'OLDER 30',
            strval($ob)
        );

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->intervalSearch(30, $ob2::INTERVAL_YOUNGER);

        $this->assertEquals(
            'YOUNGER 30',
            strval($ob2)
        );

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->intervalSearch(30, $ob3::INTERVAL_OLDER, true);

        $this->assertEquals(
            'NOT OLDER 30',
            strval($ob3)
        );

        $ob4 = new Horde_Imap_Client_Search_Query();
        $ob4->intervalSearch(30, $ob4::INTERVAL_YOUNGER, true);

        $this->assertEquals(
            'NOT YOUNGER 30',
            strval($ob4)
        );

        $ob5 = new Horde_Imap_Client_Search_Query();
        $ob5->intervalSearch(30, $ob4::INTERVAL_YOUNGER, true, array(
            'fuzzy' => true
        ));

        $this->assertEquals(
            'FUZZY NOT YOUNGER 30',
            $this->_fuzzy($ob5, array('WITHIN'))
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

    public function testModseq()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->modseq(123);

        $this->assertEquals(
            'MODSEQ 123',
            strval($ob)
        );

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->modseq(123, 'foo');

        $this->assertEquals(
            'MODSEQ "foo" all 123',
            strval($ob2)
        );

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->modseq(123, 'foo', 'shared');

        $this->assertEquals(
            'MODSEQ "foo" shared 123',
            strval($ob3)
        );

        $ob4 = new Horde_Imap_Client_Search_Query();
        $ob4->modseq(123, 'foo', 'shared', true);

        $this->assertEquals(
            'NOT MODSEQ "foo" shared 123',
            strval($ob4)
        );

        $ob5 = new Horde_Imap_Client_Search_Query();
        $ob5->modseq(123, 'foo', 'shared', true, array('fuzzy' => true));

        $this->assertEquals(
            'FUZZY NOT MODSEQ "foo" shared 123',
            $this->_fuzzy($ob5, array('CONDSTORE'))
        );
    }

    public function testPreviousSearchQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->previousSearch();

        $this->assertEquals(
            'NOT $',
            strval($ob)
        );

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->previousSearch(true);

        $this->assertEquals(
            '$',
            strval($ob2)
        );

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->previousSearch(true, array('fuzzy' => true));

        $this->assertEquals(
            'FUZZY $',
            $this->_fuzzy($ob3, array('ESEARCH', 'SEARCHRES'))
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
