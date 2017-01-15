<?php
/**
 * Unit tests for Horde_ActiveSync_Folder_Imap
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_MessageBodyDataTest extends Horde_Test_Case
{
    public function testReturnProperlyTruncatedHtml()
    {
        $factory = new Horde_ActiveSync_Factory_TestServer();
        $imap_client = $this->getMockSkipConstructor('Horde_Imap_Client_Socket');
        $imap_client->expects($this->any())
            ->method('fetch')
            ->will($this->_getFixturesFor13711());

        $imap_factory = new Horde_ActiveSync_Stub_ImapFactory();
        $imap_factory->fixture = $imap_client;
        $adapter = new Horde_ActiveSync_Imap_Adapter(array('factory' => $imap_factory));

        $this->markTestIncomplete("Can't use serialized Horde_Mime_Part");

        $horde_mime_fixture = 'TzoyMToiSG9yZGVfQWN0aXZlU3luY19NaW1lIjoyOntzOjg6IgAqAF9iYXNlIjtDOjE1OiJIb3JkZV9NaW1lX1BhcnQiOjI4MDp7YToyMDp7aTowO2k6MTtpOjE7czo0OiJ0ZXh0IjtpOjI7czo0OiJodG1sIjtpOjM7czoxNjoicXVvdGVkLXByaW50YWJsZSI7aTo0O2E6MDp7fWk6NTtzOjA6IiI7aTo2O3M6MDoiIjtpOjc7YToxOntzOjQ6InNpemUiO3M6NToiMzAzMzYiO31pOjg7YToxOntzOjc6ImNoYXJzZXQiO3M6NToidXRmLTgiO31pOjk7YTowOnt9aToxMDtzOjE6IjEiO2k6MTE7czoxOiIKIjtpOjEyO2E6MDp7fWk6MTM7TjtpOjE0O2k6MzAzMzY7aToxNTtOO2k6MTY7TjtpOjE3O2I6MDtpOjE4O2I6MDtpOjE5O047fX1zOjE4OiIAKgBfaGFzQXR0YWNobWVudHMiO047fQ==';
        $basePart = unserialize(base64_decode($horde_mime_fixture));

        $mbd = new Horde_ActiveSync_Imap_MessageBodyData(
            array(
                'imap' => $imap_client,
                'mime' => $basePart,
                'uid' => 1,
                'mbox' => new Horde_Imap_Client_Mailbox('INBOX')),
            array(
                'protocolversion' => 14.1,
                'bodyprefs' => array(
                    Horde_ActiveSync::BODYPREF_TYPE_HTML => array(
                        'truncationsize' => 10240,
                        'allornone' => 0),
                    Horde_ActiveSync::BODYPREF_TYPE_PLAIN => array(
                        'truncationsize' => 10240,
                        'allornone' => 0)
                )
            )
        );

        $this->assertEquals(10240, $mbd->html['body']->length(true));
        $this->assertEquals(true, $mbd->html['truncated']);
    }

    public function testReturnHtmlNoTruncation()
    {
        $factory = new Horde_ActiveSync_Factory_TestServer();
        $imap_client = $this->getMockSkipConstructor('Horde_Imap_Client_Socket');
        $imap_client->expects($this->any())
            ->method('fetch')
            ->will($this->_getFixturesFor13711());

        $imap_factory = new Horde_ActiveSync_Stub_ImapFactory();
        $imap_factory->fixture = $imap_client;
        $adapter = new Horde_ActiveSync_Imap_Adapter(array('factory' => $imap_factory));

        $this->markTestIncomplete("Can't use serialized Horde_Mime_Part");

        $horde_mime_fixture = 'TzoyMToiSG9yZGVfQWN0aXZlU3luY19NaW1lIjoyOntzOjg6IgAqAF9iYXNlIjtDOjE1OiJIb3JkZV9NaW1lX1BhcnQiOjI4MDp7YToyMDp7aTowO2k6MTtpOjE7czo0OiJ0ZXh0IjtpOjI7czo0OiJodG1sIjtpOjM7czoxNjoicXVvdGVkLXByaW50YWJsZSI7aTo0O2E6MDp7fWk6NTtzOjA6IiI7aTo2O3M6MDoiIjtpOjc7YToxOntzOjQ6InNpemUiO3M6NToiMzAzMzYiO31pOjg7YToxOntzOjc6ImNoYXJzZXQiO3M6NToidXRmLTgiO31pOjk7YTowOnt9aToxMDtzOjE6IjEiO2k6MTE7czoxOiIKIjtpOjEyO2E6MDp7fWk6MTM7TjtpOjE0O2k6MzAzMzY7aToxNTtOO2k6MTY7TjtpOjE3O2I6MDtpOjE4O2I6MDtpOjE5O047fX1zOjE4OiIAKgBfaGFzQXR0YWNobWVudHMiO047fQ==';
        $basePart = unserialize(base64_decode($horde_mime_fixture));

        $mbd = new Horde_ActiveSync_Imap_MessageBodyData(
            array(
                'imap' => $imap_client,
                'mime' => $basePart,
                'uid' => 1,
                'mbox' => new Horde_Imap_Client_Mailbox('INBOX')),
            array(
                'protocolversion' => 14.1,
                'bodyprefs' => array(
                    Horde_ActiveSync::BODYPREF_TYPE_HTML => array(
                        'truncationsize' => false,
                        'allornone' => 0),
                    Horde_ActiveSync::BODYPREF_TYPE_PLAIN => array(
                        'truncationsize' => false,
                        'allornone' => 0)
                )
            )
        );

        $this->assertEquals(26844, $mbd->html['body']->length(true));
        $this->assertEquals(false, $mbd->html['truncated']);
    }

    protected function _getFixturesFor13711()
    {
        $fetch_ret = unserialize(base64_decode(file_get_contents(__DIR__ . '/fixtures/fixture_fetch')));
        return $this->onConsecutiveCalls($fetch_ret);
    }

}
