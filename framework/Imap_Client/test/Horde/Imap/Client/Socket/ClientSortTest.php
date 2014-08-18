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
 * Tests for the IMAP Socket driver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Socket_ClientSortTest
extends PHPUnit_Framework_TestCase
{
    public $fetch_data;
    public $socket_ob;
    public $sort_ob;

    public function setUp()
    {
        require_once __DIR__ . '/../Stub/Socket.php';

        $this->socket_ob = new Horde_Imap_Client_Stub_Socket(array(
            'password' => 'foo',
            'username' => 'bar'
        ));
        $this->sort_ob = new Horde_Imap_Client_Socket_ClientSort(
            $this->socket_ob
        );

        // Test file is base64 encoded to obfuscate the data.
        $this->fetch_data = array_filter(explode("\n", base64_decode(
            file_get_contents(__DIR__ . '/../fixtures/clientsort.txt')
        )));
    }

    /**
     * @dataProvider clientSortProvider
     */
    public function testClientSortProvider($sort, $expected, $locale)
    {
        $ids = new Horde_Imap_Client_Ids();
        $pipeline = $this->socket_ob->pipeline();

        foreach ($this->fetch_data as $val) {
            $token = new Horde_Imap_Client_Tokenize($val);
            $token->rewind();
            $token->next();
            $ids->add($token->next());

            $this->socket_ob->doServerResponse($pipeline, $val);
        }

        $this->socket_ob->fetch_results = $pipeline->fetch;

        $sorted = $this->sort_ob->clientSort(
            $ids,
            array(
                'sort' => $sort
            )
        );

        $this->assertEquals(
            count($expected),
            count($sorted)
        );

        if (!$locale || class_exists('Collator')) {
            $this->assertEquals(
                $expected,
                array_values($sorted)
            );
        }
    }

    public function clientSortProvider()
    {
        return array(
            array(
                array(Horde_Imap_Client::SORT_SEQUENCE),
                range(1, 9),
                false
            ),
            array(
                array(Horde_Imap_Client::SORT_ARRIVAL),
                array(
                    5, // 02:30
                    6, // 03:30
                    7, // 04:30
                    8, // 05:30
                    9, // 06:30
                    1, // 07:30
                    2, // 08:30
                    3, // 09:30
                    4  // 10:30
                ),
                false
            ),
            array(
                array(Horde_Imap_Client::SORT_DATE),
                array(
                    6, // Mon, 6 Feb 1993 02:53:47 -0800 (PST)
                    9, // Wed, 08 Sep 1999 14:23:47 +0200
                    8, // Tue, 20 Jun 2000 21:21:30 -0400
                    1, // Thu, 02 May 2002 16:30:20 +0000
                    2, // Sun, 12 May 2002 00:16:32 -0500
                    3, // 24 May 2002 13:29:00 +0200
                    5, // Sun, 26 May 2002 15:15:02 -0300
                    4, // Mon, 3 Jun 2002 13:32:31 -0400
                    7  // Sun, 9 Jun 2002 19:43:35 -0400
                ),
                false
            ),
            array(
                array(Horde_Imap_Client::SORT_FROM),
                array(
                    8, // chuck
                    7, // hagendaz
                    6, // mrc
                    4, // NAVMSE-EXCHANGE1
                    1, // pear-cvs-digest-help
                    9, // philip.steeman
                    5, // publicidade
                    2, // quelatio
                    3, // Timo.Tervo
                ),
                true
            ),
            array(
                array(Horde_Imap_Client::SORT_TO),
                array(
                    8, // chagenbu
                    7, // chuck
                    6, // MRC
                    1, // pear-cvs
                    2, // quelatio
                    5, // slusarz
                    4, // slusarz2
                    9, // steeman
                    3, // timo.tervo
                ),
                true
            ),
            array(
                array(Horde_Imap_Client::SORT_DISPLAYFROM),
                array(
                    8, // Chuck
                    2, // Jesus
                    6, // Mark
                    4, // NAV
                    1, // pear
                    9, // Philip
                    5, // publicidade
                    3, // Tervo
                    7  // Walt
                ),
                true
            ),
            array(
                array(Horde_Imap_Client::SORT_DISPLAYTO),
                array(
                    4, // '
                    8, // chagenbu
                    7, // Charles Hagenbuch
                    6, // MRC
                    1, // pear
                    2, // quelatio
                    5, // slusarz
                    9, // steeman
                    3, // Timo
                ),
                true
            ),
            /* Bug #10503 */
            array(
                array(Horde_Imap_Client::SORT_SUBJECT),
                array(
                    9, // excel
                    5, // Hello
                    2, // Interesante
                    3, // Jatko
                    6, // Multi
                    4, // Norton
                    8, // pdf
                    1, // pear,
                    7 // Photo
                ),
                true
            ),
            array(
                array(Horde_Imap_Client::SORT_SIZE),
                array(
                    4, // 1762
                    5, // 3259
                    2, // 4967
                    8, // 10471
                    9, // 22262
                    1, // 38751
                    3, // 134123
                    7, // 475569
                    6  // 1845271
                ),
                false
            ),
        );
    }

    public function testClientSideThreadOrderedSubject()
    {
        $data = array(
            array(
                'Sat, 26 Jul 2008 21:10:00 -0500 (CDT)',
                'Test e-mail 1'
            ),
            array(
                'Sat, 26 Jul 2008 21:10:00 -0500 (CDT)',
                'Test e-mail 2'
            ),
            array(
                'Sat, 26 Jul 2008 22:29:20 -0500 (CDT)',
                'Re: Test e-mail 2'
            ),
            array(
                'Sat, 26 Jul 2008 21:10:00 -0500 (CDT)',
                'Test e-mail 1'
            ),
        );
        $results = new Horde_Imap_Client_Fetch_Results();

        foreach ($data as $key => $val) {
            $data = new Horde_Imap_Client_Data_Fetch();
            $data->setEnvelope(
                new Horde_Imap_Client_Data_Envelope(array(
                    'date' => $val[0],
                    'subject' => $val[1]
                ))
            );
            $results[++$key] = $data;
        }

        $thread = $this->sort_ob->threadOrderedSubject($results, true);

        foreach (array(1, 4) as $val) {
            $t = $thread->getThread($val);
            $this->assertEquals(
                array(1, 4),
                array_keys($t)
            );
            $this->assertEquals(
                1,
                $t[1]->base
            );
            $this->assertEquals(
                1,
                $t[1]->last
            );
            $this->assertEquals(
                0,
                $t[1]->level
            );
            $this->assertEquals(
                1,
                $t[4]->base
            );
            $this->assertEquals(
                1,
                $t[4]->last
            );
            $this->assertEquals(
                1,
                $t[4]->level
            );
        }

        foreach (array(2, 3) as $val) {
            $t = $thread->getThread($val);
            $this->assertEquals(
                array(2, 3),
                array_keys($t)
            );
            $this->assertEquals(
                2,
                $t[2]->base
            );
            $this->assertEquals(
                1,
                $t[2]->last
            );
            $this->assertEquals(
                0,
                $t[2]->level
            );
            $this->assertEquals(
                2,
                $t[3]->base
            );
            $this->assertEquals(
                1,
                $t[3]->last
            );
            $this->assertEquals(
                1,
                $t[3]->level
            );
        }
    }

}
