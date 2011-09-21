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
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
    public function setUp()
    {
        require_once dirname(__FILE__) . '/Stub/Socket.php';
    }

    public function testBug10503()
    {
        // Test file is base64 encoded to obfuscate the data.
        $fetch_data = base64_decode(file_get_contents(dirname(__FILE__) . '/fixtures/bug_10503.txt'));
        $imap_test_ob = new Horde_Imap_Client_Stub_Socket(array(
            'password' => '',
            'username' => ''
        ));

        $sorted = $imap_test_ob->getClientSort(
            explode("\n", $fetch_data),
            array(Horde_Imap_Client::SORT_SUBJECT)
        );

        $this->assertEquals(
            155,
            count($sorted)
        );
    }

}
