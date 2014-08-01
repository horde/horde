<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Package testing on a real (live) IMAP server.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Live_Imap extends PHPUnit_Framework_TestCase
{
    static public $config = array();

    static private $created;
    static private $imap;
    static private $test_mbox;
    static private $test_mbox_utf8;

    static public function setUpBeforeClass()
    {
        $c = array_shift(self::$config);

        self::$created = false;
        self::$test_mbox = $c['test_mbox'];
        self::$test_mbox_utf8 = $c['test_mbox_utf8'];

        try {
            $c['client_config']['cache'] = array(
                'cacheob' => new Horde_Cache(
                    new Horde_Cache_Storage_Mock(),
                    array('compress' => true)
                )
            );
        } catch (Exception $e) {}

        self::$imap = new Horde_Imap_Client_Socket(
            $c['client_config']
        );
    }

    static public function tearDownAfterClass()
    {
        if (self::$created) {
            foreach (array(self::$test_mbox, self::$test_mbox_utf8) as $val) {
                try {
                    self::$imap->deleteMailbox($val);
                } catch (Horde_Imap_Client_Exception $e) {}
            }
        }

        self::$imap = null;
    }

    public function testPreLoginCommands()
    {
        $c = self::$imap->capability;

        $this->assertInstanceOf(
            'Horde_Imap_Client_Data_Capability',
            $c
        );

        if (!$c->query('imap4rev1')) {
            $this->fail('No support of IMAP4rev1');
        }
    }

    /**
     * @depends testPreLoginCommands
     */
    public function testLogin()
    {
        /* Throws exception on error, which will prevent all further testing
         * on this server. */
        self::$imap->login();
    }

    /**
     * @depends testLogin
     */
    public function testPostLoginCapability()
    {
        /* Re-use testPreLoginCommands(). */
        $this->testPreLoginCommands();
    }

    /**
     * @depends testLogin
     */
    public function testId()
    {
        try {
            $id = self::$imap->getID();
        } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {
            $this->markTestSkipped('No support for ID extension');
        }

        $this->assertInternalType('array', $id);
    }

    /**
     * @depends testLogin
     */
    public function testGetLanguage()
    {
        try {
            $lang = self::$imap->getLanguage(true);
        } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {
            $this->markTestSkipped('No support for LANGUAGE extension');
        }

        $this->assertInternalType('array', $lang);
    }

    /**
     * @depends testLogin
     */
    public function testGetComparator()
    {
        self::$imap->getComparator();
    }

    /**
     * @depends testLogin
     */
    public function testNamespaces()
    {
        $ns = self::$imap->getNamespaces(array(), array('ob_return' => true));

        $this->assertInstanceOf(
            'Horde_Imap_Client_Namespace_List',
            $ns
        );

        // Tack on namespace information to mailbox names.
        $ns = iterator_to_array($ns);
        $ns_prefix = strval(reset($ns));

        self::$test_mbox = $ns_prefix . self::$test_mbox;
        self::$test_mbox_utf8 = $ns_prefix . self::$test_mbox_utf8;
        self::$created = true;
    }

    /**
     * @depends testNamespaces
     */
    public function testCreateMailbox()
    {
        // Delete test mailbox, if it exists.
        try {
            self::$imap->deleteMailbox(self::$test_mbox);
        } catch (Horde_Imap_Client_Exception $e) {}

        self::$imap->createMailbox(self::$test_mbox);
    }

    /**
     * @depends testCreateMailbox
     */
    public function testOpenMailbox()
    {
        self::$imap->openMailbox(
            self::$test_mbox,
            Horde_Imap_Client::OPEN_READONLY
        );
        self::$imap->openMailbox(
            self::$test_mbox,
            Horde_Imap_Client::OPEN_READWRITE
        );
        self::$imap->openMailbox(
            self::$test_mbox,
            Horde_Imap_Client::OPEN_AUTO
        );
    }

    /**
     * @depends testCreateMailbox
     */
    public function testSubscribeMailbox()
    {
        self::$imap->subscribeMailbox(self::$test_mbox, true);
        self::$imap->subscribeMailbox(self::$test_mbox, false);
    }

    /**
     * @depends testOpenMailbox
     * @depends testSubscribeMailbox
     */
    public function testRenameMailbox()
    {
        // Delete test mailbox, if it exists.
        try {
            self::$imap->deleteMailbox(self::$test_mbox_utf8);
        } catch (Horde_Imap_Client_Exception $e) {}

        self::$imap->renameMailbox(
            self::$test_mbox,
            self::$test_mbox_utf8
        );
    }

    /**
     * @depends testRenameMailbox
     */
    public function testDeleteMailbox()
    {
        self::$imap->deleteMailbox(self::$test_mbox_utf8);

        // Delete non-existent mailbox.
        try {
            self::$imap->deleteMailbox(self::$test_mbox_utf8);
        } catch (Horde_Imap_Client_Exception $e) {}
    }

    /**
     * @depends testLogin
     */
    public function testListMailbox()
    {
        // Listing all mailboxes in base level (flat format).
        $l = self::$imap->listMailboxes(
            '%',
            Horde_Imap_Client::MBOX_ALL,
            array('flat' => true)
        );
        $this->assertInternalType('array', $l);

        // Listing all mailboxes (flat format).
        $l = self::$imap->listMailboxes(
            '*',
            Horde_Imap_Client::MBOX_ALL,
            array('flat' => true)
        );
        $this->assertInternalType('array', $l);

        // Listing subscribed mailboxes (flat format).
        $l = self::$imap->listMailboxes(
            '*',
            Horde_Imap_Client::MBOX_SUBSCRIBED,
            array('flat' => true)
        );
        $this->assertInternalType('array', $l);

        // Listing unsubscribed mailboxes in base level (with attribute
        // information).
        $l = self::$imap->listMailboxes(
            '%',
            Horde_Imap_Client::MBOX_UNSUBSCRIBED,
            array('attributes' => true)
        );
        $this->assertInternalType('array', $l);
    }

    /**
     * @depends testDeleteMailbox
     */
    public function testCreateMailboxesForSelectedTests()
    {
        /* This method is mostly a placeholder to ensure that we create
         * the mailboxes for the tests that involve manipulation of the
         * contents of the mailbox. */
        self::$imap->createMailbox(self::$test_mbox);
        self::$imap->createMailbox(self::$test_mbox_utf8);
    }

    /**
     * @depends testCreateMailboxesForSelectedTests
     */
    public function testStatus()
    {
        // All status information for test mailbox.
        $s = self::$imap->status(
            self::$test_mbox,
            Horde_Imap_Client::STATUS_ALL
        );
        $this->assertInternalType('array', $s);

        // Only UIDNEXT status information for test mailbox.
        $s = self::$imap->status(
            self::$test_mbox,
            Horde_Imap_Client::STATUS_UIDNEXT
        );
        $this->assertInternalType('array', $s);

        // Only FIRSTUNSEEN, FLAGS, PERMFLAGS, HIGHESTMODSEQ, and UIDNOTSTICKY
        // status information for test mailbox.
        $s = self::$imap->status(
            self::$test_mbox,
            Horde_Imap_Client::STATUS_FIRSTUNSEEN | Horde_Imap_Client::STATUS_FLAGS | Horde_Imap_Client::STATUS_PERMFLAGS | Horde_Imap_Client::STATUS_HIGHESTMODSEQ | Horde_Imap_Client::STATUS_UIDNOTSTICKY
        );
        $this->assertInternalType('array', $s);
    }

    /**
     * @depends testStatus
     */
    public function testAppendMessagesToMailbox()
    {
        // Appending test e-mail 1 (with Flagged), 2 via a stream (with Seen),
        // 3 via a stream (with internaldate), and 4 via a string:
        $handle = fopen(__DIR__ . '/../fixtures/remote2.txt', 'r');
        $handle2 = fopen(__DIR__ . '/../fixtures/remote3.txt', 'r');
        $uid = self::$imap->append(self::$test_mbox, array(
            array(
                'data' => file_get_contents(__DIR__ . '/../fixtures/remote1.txt'),
                'flags' => array(Horde_Imap_Client::FLAG_FLAGGED)
            ),
            array(
                'data' => $handle,
                'flags' => array(Horde_Imap_Client::FLAG_SEEN)
            ),
            array(
                'data' => $handle2,
                'internaldate' => new DateTime('17 August 2003')
            ),
            array(
                'data' => file_get_contents(__DIR__ . '/../fixtures/remote4.txt')
            )
        ));

        if (!($uid instanceof Horde_Imap_Client_Ids)) {
            $this->fail('Append successful but UIDs not properly returned.');
        }

        fclose($handle);
        fclose($handle2);
    }

    /**
     * @depends testAppendMessagesToMailbox
     */
    public function testCopyingEmailToUtf8Mailbox()
    {
        $copy_uid = self::$imap->copy(
            self::$test_mbox,
            self::$test_mbox_utf8,
            array(
                'force_map' => true,
                'ids' => new Horde_Imap_Client_Ids(1, true)
            )
        );

        $this->assertEquals(
            1,
            count($copy_uid)
        );
    }

    /**
     * @depends testCopyingEmailToUtf8Mailbox
     */
    public function testDeletingMessageFromMailboxViaFlagAndExpunge()
    {
        // Flagging test e-mail 2 with the Deleted flag.
        self::$imap->store(self::$test_mbox, array(
            'add' => array(Horde_Imap_Client::FLAG_DELETED),
            'ids' => new Horde_Imap_Client_Ids(2, true)
        ));

        // Expunging mailbox by specifying non-deleted UID.
        self::$imap->expunge(
            self::$test_mbox,
            array('ids' => new Horde_Imap_Client_Ids(1, true))
        );

        // Get status of test mailbox (should have 4 messages).
        $status = self::$imap->status(
            self::$test_mbox,
            Horde_Imap_Client::STATUS_MESSAGES
        );

        $this->assertEquals(
            4,
            $status['messages']
        );

        // Expunging mailbox (should remove test e-mail 2)
        self::$imap->expunge(self::$test_mbox);

        // Get status of test mailbox (should have 3 messages).
        $status = self::$imap->status(
            self::$test_mbox,
            Horde_Imap_Client::STATUS_MESSAGES
        );

        $this->assertEquals(
            3,
            $status['messages']
        );
    }

    /**
     * @depends testDeletingMessageFromMailboxViaFlagAndExpunge
     */
    public function testMoveMessage()
    {
        // Move test e-mail 1 from utf-8 test mailbox to the test mailbox.
        self::$imap->copy(
            self::$test_mbox_utf8,
            self::$test_mbox,
            array(
                'ids' => new Horde_Imap_Client_Ids(1, true),
                'move' => true
            )
        );
    }

    /**
     * @depends testMoveMessage
     */
    public function testFlagMessageDeletedWithoutExpunging()
    {
        // Flagging test e-mail 3 with the Deleted flag.
        self::$imap->store(
            self::$test_mbox,
            array(
                'add' => array(Horde_Imap_Client::FLAG_DELETED),
                'ids' => new Horde_Imap_Client_Ids(3, true)
            )
        );

        // Closing test mailbox without expunging.
        self::$imap->close();

        // Get status of test mailbox (should have 4 messages).
        $status = self::$imap->status(
            self::$test_mbox,
            Horde_Imap_Client::STATUS_MESSAGES
        );

        $this->assertEquals(
            4,
            $status['messages']
        );
    }

    /**
     * @depends testFlagMessageDeletedWithoutExpunging
     */
    public function testSimpleSearch()
    {
        // Create a simple 'ALL' search query
        $all_query = new Horde_Imap_Client_Search_Query();

        // Search test mailbox for all messages (returning UIDs).
        $res = self::$imap->search(self::$test_mbox, $all_query);

        $this->assertEquals(
            4,
            $res['count']
        );
    }

    /**
     * @depends testFlagMessageDeletedWithoutExpunging
     */
    public function testSequenceNumberSearchReturn()
    {
        // Searching test mailbox for all messages (returning message sequence
        // numbers).
        $res = self::$imap->search(
            self::$test_mbox,
            new Horde_Imap_Client_Search_Query(),
            array(
                'results' => array(
                    Horde_Imap_Client::SEARCH_RESULTS_COUNT,
                    Horde_Imap_Client::SEARCH_RESULTS_MATCH,
                    Horde_Imap_Client::SEARCH_RESULTS_MAX,
                    Horde_Imap_Client::SEARCH_RESULTS_MIN
                ),
                'sequence' => true
            )
        );

        $this->assertEquals(
            4,
            $res['count']
        );
    }

    /**
     * @depends testFlagMessageDeletedWithoutExpunging
     */
    public function testOptimizedSearches()
    {
        // Searching test mailbox (should be optimized by using internal
        // status instead).
        $res = self::$imap->search(
            self::$test_mbox,
            new Horde_Imap_Client_Search_Query(),
            array(
                'results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT)
            )
        );

        $this->assertEquals(
            4,
            $res['count']
        );

        // No messages are recent
        $query = new Horde_Imap_Client_Search_Query();
        $query->flag(Horde_Imap_Client::FLAG_RECENT);
        $res = self::$imap->search(
            self::$test_mbox,
            $query,
            array(
                'results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT)
            )
        );

        $this->assertEquals(
            0,
            $res['count']
        );

        // All messages are unseen
        $query2 = new Horde_Imap_Client_Search_Query();
        $query2->flag(Horde_Imap_Client::FLAG_SEEN, false);
        $res = self::$imap->search(
            self::$test_mbox,
            $query2,
            array(
                'results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT)
            )
        );

        $this->assertEquals(
            4,
            $res['count']
        );
    }

    /**
     * @depends testFlagMessageDeletedWithoutExpunging
     */
    public function testMailboxSort()
    {
        // Sort test mailbox by from and reverse date for all messages.
        $res = self::$imap->search(
            self::$test_mbox,
            new Horde_Imap_Client_Search_Query(),
            array(
                'sequence' => true,
                'sort' => array(
                    Horde_Imap_Client::SORT_FROM,
                    Horde_Imap_Client::SORT_REVERSE,
                    Horde_Imap_Client::SORT_DATE
                )
            )
        );

        $this->assertEquals(
            array(3, 1, 4, 2),
            $res['match']->ids
        );
    }

    /**
     * @depends testFlagMessageDeletedWithoutExpunging
     */
    public function testMailboxThreadByReferences()
    {
        if (!self::$imap->capability->query('THREAD', 'REFERENCES')) {
            $this->markTestSkipped('Server does not support THREAD=REFERENCES');
        }

        $res = self::$imap->thread(
            self::$test_mbox,
            array(
                'criteria' => Horde_Imap_Client::THREAD_REFERENCES,
                'sequence' => true
            )
        );

        $this->assertInstanceOf(
            'Horde_Imap_Client_Data_Thread',
            $res
        );

        $this->assertEquals(
            4,
            count($res)
        );

        // Thread order: (1,4)(2)(3)
        $thread = $res->getThread(2);
        $this->assertArrayHasKey(2, $thread);
        $this->assertNull($thread[2]->base);

        $thread = $res->getThread(4);
        $this->assertArrayHasKey(1, $thread);
        $this->assertArrayHasKey(4, $thread);
        $this->assertEquals(1, $thread[1]->base);
        $this->assertEquals(1, $thread[4]->base);

        // Sort 1st 2 messages in test mailbox by thread - references
        // algorithm (UIDs).
        $ten_query = new Horde_Imap_Client_Search_Query();
        $ten_query->ids(new Horde_Imap_Client_Ids('1:2', true));
        $res = self::$imap->thread(
            self::$test_mbox,
            array(
                'criteria' => Horde_Imap_Client::THREAD_REFERENCES,
                'search' => $ten_query
            )
        );

        $this->assertInstanceOf(
            'Horde_Imap_Client_Data_Thread',
            $res
        );

        // Thread order: (1)(2)
        $thread = $res->getThread(1);
        $this->assertArrayHasKey(1, $thread);
        $this->assertNull($thread[1]->base);
    }

    /**
     * @depends testFlagMessageDeletedWithoutExpunging
     */
    public function testMailboxThreadByOrderedSubject()
    {
        // Sort test mailbox by thread - orderedsubject algorithm (sequence
        // numbers).
        $res = self::$imap->thread(
            self::$test_mbox,
            array(
                'criteria' => Horde_Imap_Client::THREAD_ORDEREDSUBJECT,
                'sequence' => true
            )
        );

        $this->assertInstanceOf(
            'Horde_Imap_Client_Data_Thread',
            $res
        );

        $this->assertEquals(
            4,
            count($res)
        );

        // Thread order: (1,4)(2)(3)
        $thread = $res->getThread(2);
        $this->assertArrayHasKey(2, $thread);
        $this->assertNull($thread[2]->base);

        $thread = $res->getThread(4);
        $this->assertArrayHasKey(1, $thread);
        $this->assertArrayHasKey(4, $thread);
        $this->assertEquals(1, $thread[1]->base);
        $this->assertEquals(1, $thread[4]->base);
    }

    /**
     * @depends testFlagMessageDeletedWithoutExpunging
     */
    public function testSimpleFetch()
    {
        $simple_fetch = new Horde_Imap_Client_Fetch_Query();
        $simple_fetch->structure();
        $simple_fetch->envelope();
        $simple_fetch->imapDate();
        $simple_fetch->size();
        $simple_fetch->flags();

        // Simple fetch example.
        $res = self::$imap->fetch(
            self::$test_mbox,
            $simple_fetch,
            array(
                'ids' => new Horde_Imap_Client_Ids(1, true)
            )
        );

        $this->assertInstanceOf(
            'Horde_Imap_Client_Fetch_Results',
            $res
        );
        $this->assertEquals(1, count($res));

        $this->assertInstanceOf(
            'Horde_Imap_Client_Data_Envelope',
            $res[1]->getEnvelope()
        );
        $this->assertEquals(
            'Test e-mail 1',
            $res[1]->getEnvelope()->subject
        );
    }

    /**
     * @depends testFlagMessageDeletedWithoutExpunging
     */
    public function testComplexFetch()
    {
        // Fetching message information from complex MIME message.
        $complex_fetch = new Horde_Imap_Client_Fetch_Query();
        $complex_fetch->fullText(array(
            'length' => 100,
            'peek' => true
        ));
        // Header of entire message
        $complex_fetch->headerText(array(
            'length' => 100,
            'peek' => true
        ));
        // Header of message/rfc822 part
        $complex_fetch->headerText(array(
            'id' => 2,
            'length' => 100,
            'peek' => true
        ));
        // Body text of entire message
        $complex_fetch->bodyText(array(
            'length' => 100,
            'peek' => true
        ));
        // Body text of message/rfc822 part
        $complex_fetch->bodyText(array(
            'id' => 2,
            'length' => 100,
            'peek' => true
        ));
        // MIME Header of multipart/alternative part
        $complex_fetch->mimeHeader('1', array(
            'length' => 100,
            'peek' => true
        ));
        // MIME Header of text/plain part embedded in message/rfc822 part
        $complex_fetch->mimeHeader('2.1', array(
            'length' => 100,
            'peek' => true
        ));
        // Body text of multipart/alternative part
        $complex_fetch->bodyPart('1', array(
            'length' => 100,
            'peek' => true
        ));
        // Body text of image/png part embedded in message/rfc822 part
        // Try to do server-side decoding, if available
        $complex_fetch->mimeHeader('2.2', array(
            'decode' => true,
            'length' => 100,
            'peek' => true
        ));
        // If supported, return decoded body part size
        $complex_fetch->bodyPartSize('2.2');
        // Select message-id header from base message header
        $complex_fetch->headers('headersearch1', array('message-id'), array(
            'length' => 100,
            'peek' => true
        ));
        // Select everything but message-id header from message/rfc822 header
        $complex_fetch->headers('headersearch2', array('message-id'), array(
            'id' => '2',
            'length' => 100,
            'notsearch' => true,
            'peek' => true
        ));
        $complex_fetch->structure();
        $complex_fetch->flags();
        $complex_fetch->imapDate();
        $complex_fetch->size();
        $complex_fetch->uid();

        if (self::$imap->capability->query('CONDSTORE')) {
            $complex_fetch->modseq();
        }

        try {
            $res = self::$imap->fetch(
                self::$test_mbox,
                $complex_fetch,
                array(
                    'ids' => new Horde_Imap_Client_Ids(3, true)
                )
            );
        } catch (Horde_Imap_Client_Exception $e) {
            if ($e->getCode() === $e::MBOXNOMODSEQ) {
                $this->markTestSkipped('Mailbox does not support MODSEQ.');
            }
            throw $e;
        }

        $this->assertInstanceOf(
            'Horde_Imap_Client_Fetch_Results',
            $res
        );
        $this->assertEquals(1, count($res));

        $this->assertEquals(
            'Message-ID: <2008yhnujm@foo.example.com>',
            trim($res[3]->getHeaders('headersearch1'))
        );

        /* Return stream instead. */
        $this->assertInternalType(
            'resource',
            $res[3]->getHeaders('headersearch1', Horde_Imap_Client_Data_Fetch::HEADER_STREAM)
        );

        /* Parse headers instead. */
        $this->assertInstanceOf(
            'Horde_Mime_Headers',
            $res[3]->getHeaders('headersearch1', Horde_Imap_Client_Data_Fetch::HEADER_PARSE)
        );
    }

    /**
     * @depends testCreateMailboxesForSelectedTests
     */
    public function testMetadata()
    {
        try {
            self::$imap->setMetadata(
                self::$test_mbox,
                array('/shared/comment' => 'test')
            );
        } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {
            $this->markTestSkipped('Server does not support METADATA.');
        }

        $res = self::$imap->getMetadata(self::$test_mbox, '/shared/comment');

        $this->assertArrayHasKey(self::$test_mbox, $res);
        $this->assertArrayHasKey('/shared/comment', $res[self::$test_mbox]);
        $this->assertEquals(
            'test',
            $res[self::$test_mbox]['/shared/comment']
        );
    }

    /**
     * @depends testSimpleSearch
     * @depends testSequenceNumberSearchReturn
     * @depends testOptimizedSearches
     * @depends testMailboxSort
     * @depends testMailboxThreadByReferences
     * @depends testMailboxThreadByOrderedSubject
     * @depends testSimpleFetch
     * @depends testComplexFetch
     */
    public function testExpungeMessagesWhileClosing()
    {
        // Re-open test mailbox READ-WRITE.
        self::$imap->openMailbox(
            self::$test_mbox,
            Horde_Imap_Client::OPEN_READWRITE
        );

        // Closing test mailbox while expunging.
        self::$imap->close(array('expunge' => true));

        // Get status of test mailbox (should have 3 messages).
        $status = self::$imap->status(
            self::$test_mbox,
            Horde_Imap_Client::STATUS_MESSAGES
        );

        $this->assertEquals(3, $status['messages']);
    }

    /**
     * @depends testLogin
     * @expectedException Horde_Imap_Client_Exception
     */
    public function testAppendToNonExistentMailbox()
    {
        // Do a test append to a non-existent mailbox - this MUST fail (RFC
        // 3501 [6.3.11]).
        self::$imap->append(
            self::$test_mbox . 'ABC',
            array(
                array(
                    'data' => file_get_contents(__DIR__ . '/../fixtures/remote1.txt'),
                    'flags' => array(Horde_Imap_Client::FLAG_FLAGGED)
                )
            )
        );
    }

}
