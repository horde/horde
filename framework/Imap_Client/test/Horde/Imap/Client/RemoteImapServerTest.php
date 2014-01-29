<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Package testing on a remote IMAP server.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_RemoteImapServerTest extends Horde_Test_Case
{
    private $imap;

    // Default test mailbox names (without namespace information)
    private $test_mbox;
    private $test_mbox_utf8;

    public function setUp()
    {
        $config = self::getConfig('IMAPCLIENT_TEST_CONFIG');
        if (is_null($config) ||
            empty($config['imapclient']['client_config']['username']) ||
            empty($config['imapclient']['client_config']['password'])) {
            $this->markTestSkipped('Remote server authentication not configured.');
        }

        if (empty($config['imapclient']['client_config']['username'])) {
            $this->markTestSkipped('IMAP server test not enabled.');
        }

        $this->test_mbox = $config['imapclient']['test_mbox'];
        $this->test_mbox_utf8 = $config['imapclient']['test_mbox_utf8'];

        try {
            $config['imapclient']['client_config']['cache'] = array(
                'cacheob' => new Horde_Cache(
                    new Horde_Cache_Storage_Mock(),
                    array('compress' => true)
                )
            );
        } catch (Exception $e) {}

        $this->imap = new Horde_Imap_Client_Socket($config['imapclient']['client_config']);
        $this->imap->login();
    }

    public function tearDown()
    {
        unset($this->imap, $this->test_mbox, $this->test_mbox_utf8);
    }

    public function testCommands()
    {
        $this->imap->capability();
        if (!$this->imap->queryCapability('imap4rev1')) {
            $this->fail('No support of IMAP4rev1');
        }

        try {
            $this->imap->getID();
            $this->imap->getLanguage();
        } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {}

        $this->imap->getComparator();

        // Tack on namespace information to mailbox names.
        $namespaces = $this->imap->getNamespaces();
        $base_ns = reset($namespaces);
        $ns_prefix = empty($base_ns['name'])
            ? ''
            : rtrim($base_ns['name'], $base_ns['delimiter']) . $base_ns['delimiter'];
        $test_mbox = $ns_prefix . $this->test_mbox;
        $test_mbox_utf8 = $ns_prefix . $this->test_mbox_utf8;

        // Create test mailbox.
        try {
            $this->imap->deleteMailbox($test_mbox);
        } catch (Horde_Imap_Client_Exception $e) {}
        try {
            $this->imap->deleteMailbox($test_mbox_utf8);
        } catch (Horde_Imap_Client_Exception $e) {}

        $this->imap->createMailbox($test_mbox);

        $this->imap->openMailbox($test_mbox, Horde_Imap_Client::OPEN_READONLY);
        $this->imap->openMailbox($test_mbox, Horde_Imap_Client::OPEN_READWRITE);
        $this->imap->openMailbox($test_mbox, Horde_Imap_Client::OPEN_AUTO);

        $this->imap->subscribeMailbox($test_mbox, true);
        $this->imap->subscribeMailbox($test_mbox, false);

        $this->imap->renameMailbox($test_mbox, $test_mbox_utf8);

        $this->imap->deleteMailbox($test_mbox_utf8);
        // Delete non-existent mailbox
        try {
            $this->imap->deleteMailbox($test_mbox_utf8);
        } catch (Horde_Imap_Client_Exception $e) {}

        // Listing all mailboxes in base level (flat format).
        $this->imap->listMailboxes('%', Horde_Imap_Client::MBOX_ALL, array('flat' => true));

        // Listing all mailboxes (flat format).
        $this->imap->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array('flat' => true));

        // Listing subscribed mailboxes (flat format).
        $this->imap->listMailboxes('*', Horde_Imap_Client::MBOX_SUBSCRIBED, array('flat' => true));

        // Listing unsubscribed mailboxes in base level (with attribute and
        // delimiter information).
        $this->imap->listMailboxes('%', Horde_Imap_Client::MBOX_UNSUBSCRIBED, array('attributes' => true, 'delimiter' => true));

        // Re-create mailboxes for tests.
        $this->imap->createMailbox($test_mbox);
        $this->imap->createMailbox($test_mbox_utf8);

        // All status information for test mailbox.
        $this->imap->status($test_mbox, Horde_Imap_Client::STATUS_ALL);

        // Only UIDNEXT status information for test mailbox.
        $this->imap->status($test_mbox, Horde_Imap_Client::STATUS_UIDNEXT);

        // Only FIRSTUNSEEN, FLAGS, PERMFLAGS, HIGHESTMODSEQ, and UIDNOTSTICKY
        // status information for test mailbox.
        $this->imap->status($test_mbox, Horde_Imap_Client::STATUS_FIRSTUNSEEN | Horde_Imap_Client::STATUS_FLAGS | Horde_Imap_Client::STATUS_PERMFLAGS | Horde_Imap_Client::STATUS_HIGHESTMODSEQ | Horde_Imap_Client::STATUS_UIDNOTSTICKY);

        // Appending test e-mail 1 (with Flagged), 2 via a stream (with Seen),
        // 3 via a stream (with internaldate), and 4 via a string:
        $handle = fopen(__DIR__ . '/fixtures/remote2.txt', 'r');
        $handle2 = fopen(__DIR__ . '/fixtures/remote3.txt', 'r');
        $uid = $this->imap->append($test_mbox, array(
            array(
                'data' => file_get_contents(__DIR__ . '/fixtures/remote1.txt'),
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
                'data' => file_get_contents(__DIR__ . '/fixtures/remote4.txt')
            )
        ));
        if (!($uid instanceof Horde_Imap_Client_Ids)) {
            throw new Horde_Imap_Client_Exception('Append successful but UIDs not properly returned.');
        }

        list($uid1, $uid2, $uid3, $uid4) = $uid->ids;
        fclose($handle);
        fclose($handle2);

        // Copying test e-mail 1 to utf-8 test mailbox.
        $uid5 = $this->imap->copy($test_mbox, $test_mbox_utf8, array('ids' => new Horde_Imap_Client_Ids($uid1)));

        // Flagging test e-mail 2 with the Deleted flag.
        $this->imap->store($test_mbox, array(
            'add' => array(Horde_Imap_Client::FLAG_DELETED),
            'ids' => new Horde_Imap_Client_Ids($uid2)
        ));

        // Expunging mailbox by specifying non-deleted UID.
        $this->imap->expunge($test_mbox, array('ids' => new Horde_Imap_Client_Ids($uid1)));

        // Get status of test mailbox (should have 4 messages).
        $status = $this->imap->status($test_mbox, Horde_Imap_Client::STATUS_MESSAGES);
        $this->assertNotEmpty($status['messages']);

        // Expunging mailbox (should remove test e-mail 2)
        $this->imap->expunge($test_mbox);

        // Get status of test mailbox (should have 3 messages).
        $status = $this->imap->status($test_mbox, Horde_Imap_Client::STATUS_MESSAGES);
        $this->assertNotEmpty($status['messages']);

        if (is_array($uid5)) {
            // Move test e-mail 1 from utf-8 test mailbox to the test mailbox.
            $this->imap->copy($test_mbox_utf8, $test_mbox, array(
                'ids' => new Horde_Imap_Client_Ids(reset($uid5)),
                'move' => true
            ));
        }

        // Deleting utf-8 test mailbox.
        $this->imap->deleteMailbox($test_mbox_utf8);

        // Flagging test e-mail 3 with the Deleted flag.
        $this->imap->store($test_mbox, array(
            'add' => array(Horde_Imap_Client::FLAG_DELETED),
            'ids' => new Horde_Imap_Client_Ids($uid3)
        ));

        // Closing test mailbox without expunging.
        $this->imap->close();

        // Get status of test mailbox (should have 4 messages).
        $status = $this->imap->status($test_mbox, Horde_Imap_Client::STATUS_MESSAGES);
        $this->assertNotEmpty($status['messages']);

        // Create a simple 'ALL' search query
        $all_query = new Horde_Imap_Client_Search_Query();

        // Search test mailbox for all messages (returning UIDs).
        $res = $this->imap->search($test_mbox, $all_query);
        $this->assertNotEmpty($res['count']);

        // Searching test mailbox for all messages (returning message sequence
        // numbers).
        $res = $this->imap->search($test_mbox, $all_query, array(
            'results' => array(
                Horde_Imap_Client::SEARCH_RESULTS_COUNT,
                Horde_Imap_Client::SEARCH_RESULTS_MATCH,
                Horde_Imap_Client::SEARCH_RESULTS_MAX,
                Horde_Imap_Client::SEARCH_RESULTS_MIN
            ),
            'sequence' => true
        ));
        $this->assertNotEmpty($res['count']);

        // Searching test mailbox (should be optimized by using internal
        // status instead).
        $query1 = $query2 = $all_query;
        $this->imap->search($test_mbox, $all_query, array(
            'results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT)
        ));
        $query1->flag(Horde_Imap_Client::FLAG_RECENT);
        $this->imap->search($test_mbox, $query1, array(
            'results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT)
        ));
        $query2->flag(Horde_Imap_Client::FLAG_SEEN, false);
        $this->imap->search($test_mbox, $query2, array(
            'results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT)
        ));
        $this->imap->search($test_mbox, $query2, array(
            'results' => array(Horde_Imap_Client::SEARCH_RESULTS_MIN)
        ));

        // Sort test mailbox by from and reverse date for all messages
        // (returning UIDs).
        $this->imap->search($test_mbox, $all_query, array(
            'sort' => array(
                Horde_Imap_Client::SORT_FROM,
                Horde_Imap_Client::SORT_REVERSE,
                Horde_Imap_Client::SORT_DATE
            )
        ));

        // Sort test mailbox by thread - references algorithm (UIDs).
        $thread_algo = (($thread = $this->imap->queryCapability('THREAD')) && isset($thread['REFERENCES']))
            ? Horde_Imap_Client::THREAD_REFERENCES
            : Horde_Imap_Client::THREAD_ORDEREDSUBJECT;

        $this->imap->thread($test_mbox, array(
            'criteria' => $thread_algo
        ));

        // Sort 1st 5 messages in test mailbox by thread - references
        // algorithm (UIDs).
        $ten_query = new Horde_Imap_Client_Search_Query();
        $ten_query->ids(new Horde_Imap_Client_Ids('1:5', true));
        $this->imap->thread($test_mbox, array(
            'criteria' => $thread_algo,
            'search' => $ten_query
        ));

        // Sort test mailbox by thread - orderedsubject algorithm (sequence
        // numbers).
        $this->imap->thread($test_mbox, array(
            'criteria' => Horde_Imap_Client::THREAD_ORDEREDSUBJECT,
            'sequence' => true
        ));

        $simple_fetch = new Horde_Imap_Client_Fetch_Query();
        $simple_fetch->structure();
        $simple_fetch->envelope();
        $simple_fetch->imapDate();
        $simple_fetch->size();
        $simple_fetch->flags();

        // Simple fetch example.
        $res = $this->imap->fetch($test_mbox, $simple_fetch);

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
        $complex_fetch->envelope();
        $complex_fetch->flags();
        $complex_fetch->imapDate();
        $complex_fetch->size();
        $complex_fetch->uid();

        if ($this->imap->queryCapability('CONDSTORE')) {
            $complex_fetch->modseq();
        }

        $this->imap->fetch($test_mbox, $complex_fetch, array(
            'ids' => new Horde_Imap_Client_Ids($uid4)
        ));

        // Fetching parsed header information (requires Horde MIME library).
        $hdr_fetch = new Horde_Imap_Client_Fetch_Query();
        $hdr_fetch->headers('headersearch1', array('message-id'), array(
            'parse' => true,
            'peek' => true
        ));

        $this->imap->fetch($test_mbox, $hdr_fetch, array(
            'ids' => new Horde_Imap_Client_Ids($uid4)
        ));

        // Test METADATA on test mailbox.
        try {
            $this->imap->setMetadata($test_mbox, array('/shared/comment' => 'test'));
            $this->imap->getMetadata($test_mbox, '/shared/comment');
        } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {}

        // Re-open test mailbox READ-WRITE.
        $this->imap->openMailbox($test_mbox, Horde_Imap_Client::OPEN_READWRITE);

        // Closing test mailbox while expunging.
        $this->imap->close(array('expunge' => true));

        // Get status of test mailbox (should have 3 messages).
        $status = $this->imap->status($test_mbox, Horde_Imap_Client::STATUS_MESSAGES);
        $this->assertNotEmpty($status['messages']);

        // Deleting test mailbox.
        $this->imap->deleteMailbox($test_mbox);

        // Do a test append to a non-existent mailbox - this MUST fail (RFC
        // 3501 [6.3.11]).
        try {
            $this->imap->append($test_mbox, array(
                array(
                    'data' => file_get_contents(__DIR__ . '/fixtures/remote1.txt'),
                    'flags' => array(Horde_Imap_Client::FLAG_FLAGGED)
                )
            ));
            $this->fail('Expecting exception.');
        } catch (Horde_Imap_Client_Exception $e) {}
    }

}
