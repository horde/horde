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
    }

    public function testBug10503()
    {
        // Test file is base64 encoded to obfuscate the data.
        $fetch_data = explode("\n", base64_decode(
            file_get_contents(__DIR__ . '/../fixtures/bug_10503.txt')
        ));

        $ids = new Horde_Imap_Client_Ids();
        $pipeline = $this->socket_ob->pipeline();

        foreach (array_filter($fetch_data) as $val) {
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
                'sort' => array(Horde_Imap_Client::SORT_SUBJECT)
            )
        );

        $this->assertEquals(
            9,
            count($sorted)
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
