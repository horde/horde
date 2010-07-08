<?php
/**
 * Base for PHPUnit scenarios.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Base for PHPUnit scenarios.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Test_Filter
{
    /**
     * Handle a "given" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runGiven(&$world, $action, $arguments)
    {
        switch($action) {
        case 'a set of Kolab test servers':
            $world['servers'] = array();
            $world['servers']['server'] = $this->prepareEmptyKolabServer();
            $world['servers']['freebusy'] = $this->prepareEmptyKolabServer();
            break;
        case 'a set of test accounts':
            foreach ($world['servers'] as $server) {
                $this->prepareUsers($server['server']);
            }
        case 'a set of events where the test resource is busy':
            foreach ($world['servers'] as $server) {
                $this->prepareUsers($server);
            }
        default:
            return parent::runGiven($world, $action, $arguments);
        }
    }

    /**
     * Handle a "when" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runWhen(&$world, $action, $arguments)
    {
        switch($action) {
        case 'inviting the resource':
        default:
            return parent::runWhen($world, $action, $arguments);
        }
    }

    /**
     * Handle a "then" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runThen(&$world, $action, $arguments)
    {
        switch($action) {
        case 'the invitation is being rejected':
        case 'the invitation is being accepted':
        case 'the resource contains the event':
        case 'the resource is busy during':
        case 'the response contains':
        default:
            return parent::runThen($world, $action, $arguments);
        }
    }

    /**
     * Fill a Kolab Server with test users.
     *
     * @param Kolab_Server &$server The server to populate.
     *
     * @return Horde_Kolab_Server The empty server.
     */
    public function prepareUsers(&$server)
    {
        parent::prepareUsers(&$server);
        $result = $server->add($this->provideFilterUserOne());
        $this->assertNoError($result);
        $result = $server->add($this->provideFilterUserTwo());
        $this->assertNoError($result);
        $result = $server->add($this->provideFilterUserThree());
        $this->assertNoError($result);
        $result = $server->add($this->provideFilterCalendarUser());
        $this->assertNoError($result);
    }

    /**
     * Prepare the configuration.
     *
     * @return NULL
     */
    public function prepareConfiguration()
    {
        $fh = fopen(HORDE_BASE . '/config/conf.php', 'w');
        $data = <<<EOD
\$conf['use_ssl'] = 2;
\$conf['server']['name'] = \$_SERVER['SERVER_NAME'];
\$conf['server']['port'] = \$_SERVER['SERVER_PORT'];
\$conf['debug_level'] = E_ALL;
\$conf['umask'] = 077;
\$conf['compress_pages'] = true;
\$conf['menu']['always'] = false;
\$conf['portal']['fixed_blocks'] = array();
\$conf['imsp']['enabled'] = false;

/** Additional config variables required for a clean Horde setup */
\$conf['session']['use_only_cookies'] = false;
\$conf['session']['timeout'] = 0;
\$conf['cookie']['path'] = '/';
\$conf['cookie']['domain'] = \$_SERVER['SERVER_NAME'];
\$conf['use_ssl'] = false;
\$conf['session']['cache_limiter'] = 'nocache';
\$conf['session']['name'] = 'Horde';
\$conf['log']['enabled'] = false;
\$conf['prefs']['driver'] = 'session';
\$conf['auth']['driver'] = 'kolab';
\$conf['share']['driver'] = 'kolab';
\$conf['debug_level'] = E_ALL;

/** Make the share driver happy */
\$conf['kolab']['enabled'] = true;

/** Ensure we still use the LDAP test driver */
\$conf['kolab']['server']['driver'] = 'test';

/** Ensure that we do not trigger on folder update */
\$conf['kolab']['no_triggering'] = true;

/** Storage location for the free/busy system */
\$conf['fb']['cache_dir']             = '/tmp';
\$conf['kolab']['freebusy']['server'] = 'https://fb.example.org/freebusy';

/** Setup the virtual file system for Kolab */
\$conf['vfs']['params']['all_folders'] = true;
\$conf['vfs']['type'] = 'kolab';

\$conf['kolab']['imap']['server'] = 'localhost';
\$conf['kolab']['imap']['port']   = 0;
\$conf['kolab']['imap']['allow_special_users'] = true;
\$conf['kolab']['filter']['reject_forged_from_header'] = false;
\$conf['kolab']['filter']['email_domain'] = 'example.org';
\$conf['kolab']['filter']['privileged_networks'] = '127.0.0.1,192.168.0.0/16';
\$conf['kolab']['filter']['verify_from_header'] = true;
\$conf['kolab']['filter']['calendar_id'] = 'calendar';
\$conf['kolab']['filter']['calendar_pass'] = 'calendar';
\$conf['kolab']['filter']['lmtp_host'] = 'imap.example.org';
\$conf['kolab']['filter']['simple_locks'] = true;
\$conf['kolab']['filter']['simple_locks_timeout'] = 3;

\$conf['kolab']['filter']['itipreply']['driver'] = 'echo';
\$conf['kolab']['filter']['itipreply']['params']['host'] = 'localhsot';
\$conf['kolab']['filter']['itipreply']['params']['port'] = 25;

\$conf['freebusy']['driver'] = 'Mock';
EOD;
        fwrite($fh, "<?php\n" . $data);
        fclose($fh);
    }

    /**
     * Return a test user.
     *
     * @return array The test user.
     */
    public function provideFilterUserOne()
    {
        return array(
            'type' => 'Horde_Kolab_Server_Object_Kolab_User',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_GIVENNAME => 'Me',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SN => 'Me',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_MAIL => 'me@example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SID => 'me',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_USERPASSWORD => 'me',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_HOMESERVER => 'home.example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_IMAPHOST => 'imap.example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FREEBUSYHOST => 'https://fb.example.org/freebusy',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_IPOLICY => array('ACT_REJECT_IF_CONFLICTS'),
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_ALIAS => array('me.me@example.org', 'MEME@example.org'),
        );
    }

    /**
     * Return a test user.
     *
     * @return array The test user.
     */
    public function provideFilterUserTwo()
    {
        return array(
            'type' => 'Horde_Kolab_Server_Object_Kolab_User',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_GIVENNAME => 'You',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SN => 'You',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_MAIL => 'you@example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SID => 'you',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_USERPASSWORD => 'you',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_HOMESERVER => 'home.example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_IMAPHOST => 'home.example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FREEBUSYHOST => 'https://fb.example.org/freebusy',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_ALIAS => array('you.you@example.org'),
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_DELEGATE => array('wrobel@example.org'),
        );
    }

    /**
     * Return a test user.
     *
     * @return array The test user.
     */
    public function provideFilterUserThree()
    {
        return array(
            'type' => 'Horde_Kolab_Server_Object_Kolab_User',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_GIVENNAME => 'Else',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SN => 'Else',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_MAIL => 'else@example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SID => 'else',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_USERPASSWORD => 'you',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_HOMESERVER => 'imap.example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_IMAPHOST => 'imap.example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FREEBUSYHOST => 'https://fb.example.org/freebusy',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_DELEGATE => array('me@example.org'),
        );
    }

    /**
     * Return the calendar user.
     *
     * @return array The calendar user.
     */
    public function provideFilterCalendarUser()
    {
        return array(
            'type' => 'Horde_Kolab_Server_Object_Kolab_User',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_CN => 'calendar',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_GIVENNAME => '',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SN => 'calendar',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_MAIL => 'calendar@example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SID => 'calendar@home.example.com',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_USERPASSWORD => 'calendar',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_HOMESERVER => 'home.example.org',
            Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_IMAPHOST => 'imap.example.org',
        );
    }

    public function sendFixture($infile, $outfile, $user, $client, $from, $to,
                                $host, $params = array())
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0],
                                 '--sender=' . $from,
                                 '--recipient=' . $to,
                                 '--user=' . $user,
                                 '--host=' . $host,
                                 '--client=' . $client);

        $in = file_get_contents($infile, 'r');

        $tmpfile = Horde_Util::getTempFile('KolabFilterTest');
        $tmpfh = @fopen($tmpfile, 'w');
        if (empty($params['unmodified_content'])) {
            @fwrite($tmpfh, sprintf($in, $from, $to));
        } else {
            @fwrite($tmpfh, $in);
        }
        @fclose($tmpfh);

        $inh = @fopen($tmpfile, 'r');

        /* Setup the class */
        if (empty($params['incoming'])) {
            require_once 'Horde/Kolab/Filter/Content.php';
            $parser = new Horde_Kolab_Filter_Content();
        } else {
            require_once 'Horde/Kolab/Filter/Incoming.php';
            $parser = new Horde_Kolab_Filter_Incoming();
        }

        ob_start();

        /* Parse the mail */
        $result = $parser->parse($inh, 'echo');
        if (empty($params['error'])) {
            $this->assertNoError($result);
            $this->assertTrue(empty($result));

            $output = ob_get_contents();
            ob_end_clean();

            $out = file_get_contents($outfile);
            $replace = array(
                '/^Received:.*$/m' => '',
                '/^Date:.*$/m' => '',
                '/DTSTAMP:.*$/m' => '',
                '/^--+=.*$/m' => '----',
                '/^Message-ID.*$/m' => '----',
                '/boundary=.*$/m' => '----',
                '/\s/' => '',
            );
            foreach ($replace as $pattern => $replacement) {
                $output = preg_replace($pattern, $replacement, $output);
                $out    = preg_replace($pattern, $replacement, $out);
            }

            if (empty($params['unmodified_content'])) {
                $this->assertEquals(sprintf($out, $from, $to), $output);
            } else {
                $this->assertEquals($out, $output);
            }
        } else {
            $this->assertError($result, $params['error']);
        }

    }
}
