<?php
/**
 * Tests for the IMAP Socket driver.
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
 * Tests for the IMAP Socket driver.
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
class Horde_Imap_Client_SocketTest extends PHPUnit_Framework_TestCase
{
    public $test_ob;

    public function setUp()
    {
        require_once __DIR__ . '/Stub/Socket.php';

        $this->test_ob = new Horde_Imap_Client_Stub_Socket(array(
            'password' => 'foo',
            'username' => 'bar'
        ));
    }

    public function testBug10503()
    {
        // Test file is base64 encoded to obfuscate the data.
        $fetch_data = base64_decode(file_get_contents(__DIR__ . '/fixtures/bug_10503.txt'));
        $this->test_ob = new Horde_Imap_Client_Stub_Socket(array(
            'password' => 'foo',
            'username' => 'bar'
        ));

        $sorted = $this->test_ob->getClientSort(
            explode("\n", $fetch_data),
            array(Horde_Imap_Client::SORT_SUBJECT)
        );

        $this->assertEquals(
            9,
            count($sorted)
        );
    }

    public function testSimpleThreadParse()
    {
        $data = '(1)';
        $thread = $this->test_ob->getThreadSort($data);

        $this->assertTrue($thread instanceof Horde_Imap_Client_Data_Thread);

        $list = $thread->messageList();
        $this->assertTrue($list instanceof Horde_Imap_Client_Ids);
        $this->assertEquals(
            array(1),
            $list->ids
        );

        $this->assertEquals(
            1,
            count($thread)
        );

        $this->assertEquals(
            array(1),
            unserialize(serialize($list))->ids
        );

        $thread_list = $thread->getThread(1);

        $this->assertEquals(
            1,
            count($thread_list)
        );
        $this->assertTrue(is_object($thread_list[1]));
        $this->assertTrue(is_null($thread_list[1]->base));
        $this->assertTrue($thread_list[1]->last);
        $this->assertEquals(
            0,
            $thread_list[1]->level
        );

        $thread_list = $thread->getThread(2);
        $this->assertTrue(empty($thread_list));
    }

    public function testComplexThreadParse()
    {
        $data = '((1)(2)(3)(4 (5)(6))(7 8)(9)(10 (11 12)(13 (14 (15)))))(16 17)';
        $thread = $this->test_ob->getThreadSort($data);

        $list = $thread->messageList();
        $this->assertTrue($list instanceof Horde_Imap_Client_Ids);
        $this->assertEquals(
            range(1, 17),
            $list->ids
        );

        $this->assertEquals(
            range(1, 17),
            unserialize(serialize($list))->ids
        );

        $thread_list = $thread->getThread(16);
        $this->assertTrue(!empty($thread_list));

        $thread_list = $thread->getThread(18);
        $this->assertTrue(empty($thread_list));

        $thread_list = $thread->getThread(10);
        $this->assertEquals(
            15,
            count($thread_list)
        );

        foreach (array(1, 2, 3, 4, 5, 7, 9, 11) as $k) {
            $this->assertFalse($thread_list[$k]->last);
        }
        foreach (array(6, 8, 10, 12, 13, 14, 15) as $k) {
            $this->assertTrue($thread_list[$k]->last);
        }

        foreach (range(1, 15) as $k) {
            $this->assertEquals(
                1,
                $thread_list[$k]->base
            );
        }

        foreach (array(1, 2, 3, 4, 7, 9, 10) as $k) {
            $this->assertEquals(
                0,
                $thread_list[$k]->level
            );
        }
        foreach (array(5, 6, 8, 11, 13) as $k) {
            $this->assertEquals(
                1,
                $thread_list[$k]->level
            );
        }
        foreach (array(12, 14) as $k) {
            $this->assertEquals(
                2,
                $thread_list[$k]->level
            );
        }
        foreach (array(15) as $k) {
            $this->assertEquals(
                3,
                $thread_list[$k]->level
            );
        }
    }

    public function testBug11450()
    {
        // * NAMESPACE (("INBOX." ".")) (("user." ".")) (("" "."))
        $data = '(("INBOX." ".")) (("user." ".")) (("" "."))';

        $this->assertEquals(
            3,
            count($this->test_ob->parseNamespace($data))
        );
    }

    public function testLargeEnvelopeData()
    {
        $test = '* 1 FETCH (ENVELOPE ("Fri, 28 Sep 2012 17:09:32 -0700" {10000}' .
            str_repeat('F', 10000) .
            ' ((NIL NIL "foo" "example.com")) ((NIL NIL "foo" "example.com")) ((NIL NIL "foo" "example.com")) (' .
            str_repeat('(NIL NIL "bar" "example.com")', 50000) .
            ') NIL NIL NIL "<123@example.com>"))';

        $this->test_ob->setParam('envelope_addrs', 1000);
        $this->test_ob->setParam('envelope_string', 2000);

        $env = $this->test_ob->parseFetch($test)->first()->getEnvelope();

        $this->assertEquals(
            1000,
            count($env->to)
        );
        $this->assertEquals(
            2000,
            strlen($env->subject)
        );
    }

    public function testUntaggedResponseAlert()
    {
        // Bug #11453
        $test = '* NO [ALERT] Foo Bar';

        $this->test_ob->responseCode($test);

        $alerts = $this->test_ob->alerts();

        $this->assertEquals(
            1,
            count($alerts)
        );

        $this->assertEquals(
            'Foo Bar',
            reset($alerts)
        );
    }

}
