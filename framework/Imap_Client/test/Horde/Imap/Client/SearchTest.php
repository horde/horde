<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */

/**
 * Tests for the Search Query object.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @ignore
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_SearchTest extends PHPUnit_Framework_TestCase
{
    public function testOrQueries()
    {
        $ob = new Horde_Imap_Client_Search_Query();

        $ob2 = new Horde_Imap_Client_Search_Query();
        $ob2->flag('\\deleted', false);
        $ob2->headerText('from', 'ABC');

        $ob3 = new Horde_Imap_Client_Search_Query();
        $ob3->flag('\\deleted', true);
        $ob3->headerText('from', 'DEF');

        $ob->orSearch(array($ob2, $ob3));

        $this->assertEquals(
            'OR (DELETED FROM DEF) (UNDELETED FROM ABC)',
            strval($ob)
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

}
