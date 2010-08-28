<?php
/**
 * Test the content filter class within the Kolab filter implementation.
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the unit test framework
 */
require_once 'Horde/Kolab/Test/Filter.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Filter/Content.php';

/**
 * Test the content filter.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_ContentTest extends Horde_Kolab_Test_Filter
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        $result = $this->prepareBasicSetup();

        $this->server  = &$result['server'];
        $this->storage = &$result['storage'];
        $this->auth    = &$result['auth'];

        global $conf;

        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;
        $conf['kolab']['imap']['allow_special_users'] = true;
        $conf['kolab']['filter']['reject_forged_from_header'] = false;
        $conf['kolab']['filter']['email_domain'] = 'example.org';
        $conf['kolab']['filter']['privileged_networks'] = '127.0.0.1,192.168.0.0/16';
        $conf['kolab']['filter']['verify_from_header'] = true;
        $conf['kolab']['filter']['calendar_id'] = 'calendar';
        $conf['kolab']['filter']['calendar_pass'] = 'calendar';
        $conf['kolab']['filter']['lmtp_host'] = 'imap.example.org';
        $conf['kolab']['filter']['simple_locks'] = true;
        $conf['kolab']['filter']['simple_locks_timeout'] = 3;

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getNewFolder();
        $folder->setName('Kalender');
        $result = $folder->save(array('type' => 'event',
                                      'default' => true));
        $this->assertNoError($result);
    }

    /**
     * Test sending messages through the content filter.
     *
     * @dataProvider addressCombinations
     */
    public function testContentHandler($infile, $outfile, $user, $client, $from,
                                       $to, $host, $params = array())
    {
        $this->sendFixture($infile, $outfile, $user, $client, $from, $to,
                           $host, $params);
    }

    /**
     * Provides various test situations for the Kolab content filter.
     */
    public function addressCombinations()
    {
        return array(
            /**
             * Test a simple message
             */
            array(dirname(__FILE__) . '/fixtures/vacation.eml',
                  dirname(__FILE__) . '/fixtures/vacation.ret',
                  '', '', 'me@example.org', 'you@example.net', 'example.org',
                  array('unmodified_content' => true)),
            /**
             * Test a simple message
             */
            array(dirname(__FILE__) . '/fixtures/tiny.eml',
                  dirname(__FILE__) . '/fixtures/tiny.ret',
                  '', '', 'me@example.org', 'you@example.org', 'example.org',
                  array('unmodified_content' => true)),
            /**
             * Test a simple message
             */
            array(dirname(__FILE__) . '/fixtures/simple.eml',
                  dirname(__FILE__) . '/fixtures/simple_out.ret',
                  '', '', 'me@example.org', 'you@example.org', 'example.org',
                  array('unmodified_content' => true)),
            /**
             * Test sending from a remote server without authenticating. This
             * will be considered forging the sender.
             */
 /*           array(dirname(__FILE__) . '/fixtures/forged.eml',
                  dirname(__FILE__) . '/fixtures/forged.ret',
                  '', '10.0.0.1', 'me@example.org', 'you@example.org', 'example.org',
                  array('unmodified_content' => true)),*/
            /**
             * Test sending from a remote server without authenticating but
             * within the priviledged network. This will not be considered
             * forging the sender.
             */
            array(dirname(__FILE__) . '/fixtures/forged.eml',
                  dirname(__FILE__) . '/fixtures/privileged.ret',
                  '', '192.168.178.1', 'me@example.org', 'you@example.org', 'example.org',
                  array('unmodified_content' => true)),
            /**
             * Test authenticated sending of a message from a remote client.
             */
            array(dirname(__FILE__) . '/fixtures/validation.eml',
                  dirname(__FILE__) . '/fixtures/validation.ret',
                  'me@example.org', 'remote.example.org', 'me@example.org', 'you@example.org', 'example.org'),
            /**
             * Test authenticated sending of a message from a remote client
             * using an alias.
             */
            array(dirname(__FILE__) . '/fixtures/validation.eml',
                  dirname(__FILE__) . '/fixtures/validation.ret',
                  'me@example.org', 'remote.example.org', 'me.me@example.org', 'you@example.org', 'example.org'),
            /**
             * Test authenticated sending of a message from a remote client
             * using an alias with capitals (MEME@example.org).
             */
            array(dirname(__FILE__) . '/fixtures/validation.eml',
                  dirname(__FILE__) . '/fixtures/validation.ret',
                  'me@example.org', 'remote.example.org', 'meme@example.org', 'you@example.org', 'example.org'),
            /**
             * Test authenticated sending of a message from a remote client
             * as delegate
             */
            array(dirname(__FILE__) . '/fixtures/validation.eml',
                  dirname(__FILE__) . '/fixtures/validation.ret',
                  'me@example.org', 'remote.example.org', 'else@example.org', 'you@example.org', 'example.org'),
            /**
             * Test authenticated sending of a message from a remote client
             * with an address that is not allowed.
             */
            array(dirname(__FILE__) . '/fixtures/validation.eml',
                  dirname(__FILE__) . '/fixtures/validation.ret',
                  'me@example.org', 'remote.example.org', 'else3@example.org', 'you@example.org', 'example.org',
                  array('error' =>'Invalid From: header. else3@example.org looks like a forged sender')),
            /**
             * Test forwarding an invitation
             */
            array(dirname(__FILE__) . '/fixtures/invitation_forward.eml',
                  dirname(__FILE__) . '/fixtures/invitation_forward.ret',
                  'me@example.org', '10.0.2.1', 'me@example.org', 'you@example.org', 'example.org'),
        );
    }

    /**
     * Test rejecting a forged from header.
     */
    public function testRejectingForgedFromHeader()
    {
        global $conf;

        $conf['kolab']['filter']['reject_forged_from_header'] = true;

        $this->sendFixture(dirname(__FILE__) . '/fixtures/forged.eml',
                           dirname(__FILE__) . '/fixtures/forged.ret',
                           '', '10.0.0.1', 'me@example.org', 'you@example.org', 'example.org',
                           array('error' =>'Invalid From: header. me@example.org looks like a forged sender',
                                 'unmodified_content' => true));
    }

    /**
     * Test translated forged from headers.
     */
    public function testTranslatedForgedFromHeader()
    {
        $this->markTestIncomplete('Some the translation does not kick in.');
        global $conf;

        $conf['kolab']['filter']['locale_path'] = dirname(__FILE__) . '/../../../../../data/Kolab_Filter/locale';
        $conf['kolab']['filter']['locale'] = 'de_DE';

        $this->sendFixture(dirname(__FILE__) . '/fixtures/forged.eml',
                           dirname(__FILE__) . '/fixtures/forged_trans.ret',
                           '', '10.0.0.1', 'me@example.org', 'you@example.org', 'example.org',
                           array('unmodified_content' => true));
    }

}
