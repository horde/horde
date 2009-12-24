<?php
/**
 * Test the incoming filter class for its load behaviour..
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the unit test framework
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Extensions/PerformanceTestCase.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Filter/Incoming.php';

/**
 * Test the incoming filter load.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_LoadTest extends PHPUnit_Extensions_PerformanceTestCase
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        global $conf;

        $conf = array();
        $conf['log']['enabled']          = false;

        $conf['kolab']['filter']['debug'] = true;

        $conf['kolab']['server'] = array(
            'driver' => 'test',
            'params' => array(
                'data' => array(
                    'cn=me' => array(
                        'dn' => 'cn=me',
                        'data' => array(
                            'objectClass' => array('kolabInetOrgPerson'),
                            'mail' => array('me@example.com'),
                            'kolabImapHost' => array('localhost'),
                            'uid' => array('me'),
                        )
                    ),
                    'cn=you' => array(
                        'dn' => 'cn=you',
                        'data' => array(
                            'objectClass' => array('kolabInetOrgPerson'),
                            'mail' => array('you@example.com'),
                            'kolabImapHost' => array('localhost'),
                            'uid' => array('you'),
                        )
                    ),
                ),
            )
        );
        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;
        $conf['kolab']['imap']['allow_special_users'] = true;

        $_SERVER['SERVER_NAME'] = 'localhost';
   }


    /**
     * Test the time the script takes in handling some messages.
     */
    public function testLoad()
    {
        $this->setMaxRunningTime(3);

        $tmpdir = Horde::getTempDir();
        $tmpfile = @tempnam($tmpdir, 'BIG.eml.');
        $tmpfh = @fopen($tmpfile, "w");
        $head = file_get_contents(dirname(__FILE__) . '/fixtures/tiny.eml');
        $body = '';
        for ($i = 0; $i < 50000;$i++) {
            $body .= md5(microtime());
            if (($i % 2) == 0) {
                $body .= "\n";
            }
        }
        @fwrite($tmpfh, $head);
        @fwrite($tmpfh, $body);
        @fclose($tmpfh);

        $_SERVER['argv'] = array($_SERVER['argv'][0], '--sender=me@example.com', '--recipient=you@example.com', '--user=', '--host=example.com');

        for ($i = 0; $i < 10; $i++) {

            $parser = &new Horde_Kolab_Filter_Incoming();
            $inh = fopen(dirname(__FILE__) . '/fixtures/tiny.eml', 'r');
            $parser->parse($inh, 'drop');

            $parser = &new Horde_Kolab_Filter_Incoming();
            $inh = fopen(dirname(__FILE__) . '/fixtures/simple.eml', 'r');
            $parser->parse($inh, 'drop');

            $parser = &new Horde_Kolab_Filter_Incoming();
            $inh = fopen($tmpfile, 'r');
            $parser->parse($inh, 'drop');

        }
    }
}
