<?php
/**
 * Test cases for Ingo_Script:: and derived classes
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author     Jason M. Felice <jason.m.felice@gmail.com>
 * @package    Ingo
 * @subpackage UnitTests
 */

class Ingo_Unit_ScriptTest extends Ingo_Unit_TestBase
{
    public function setUp()
    {
        $this->markTestIncomplete('TODO');
    }

    function testBlacklistRuleWithoutFolderWillDiscardMatchingMessage()
    {
        $runner = ScriptTester::factory('all', $this);

        $ob = new Ingo_Rule_System_Blacklist();
        $ob->addAddresses('spammer@example.com');
        $runner->addRule($ob);

        $runner->assertDeletesMessage('from_spammer');
        $runner->assertKeepsMessage('not_from_spammer');
    }

    function testWhitelistRuleWillPreventDeletionOfBlacklistedMessage()
    {
        $runner = ScriptTester::factory('all', $this);

        $bl = new Ingo_Rule_System_Blacklist();
        $bl->addAddresses('spammer@example.com');
        $runner->addRule($bl);

        $wl = new Ingo_Rule_System_Whitelist();
        $wl->addAddresses('spammer@example.com');
        $runner->addRule($wl);

        $runner->assertKeepsMessage('from_spammer');
        $runner->assertKeepsMessage('not_from_spammer');
    }

    function testBlacklistRuleWithFolderWillMoveMatchingMessages()
    {
        $runner = ScriptTester::factory('all', $this);

        $ob = new Ingo_Rule_System_Blacklist();
        $ob->addAddresses('spammer@example.com');
        $ob->mailbox = 'Junk';
        $runner->addRule($ob);

        $runner->assertMovesMessage('from_spammer', 'Junk');
    }

    function testPartialWhitelistAddressShouldNotMatch()
    {
        $runner = ScriptTester::factory('all', $this);

        $bl = new Ingo_Rule_System_Blacklist();
        $bl->addAddresses('spammer@example.com');
        $runner->addRule($bl);

        $wl = new Ingo_Rule_System_Whitelist();
        $wl->addAddresses('ammer@example.com');
        $runner->addRule($wl);

        $runner->assertDeletesMessage('from_spammer');
    }

    function testPartialBlacklistAddressShouldNotMatch()
    {
        $runner = ScriptTester::factory('all', $this);

        $bl = new Ingo_Storage_Blacklist();
        $bl->addAddresses('ammer@example.com');
        $runner->addRule($bl);

        $runner->assertKeepsMessage('from_spammer');
    }

}

/**
 * Abstract base class for strategies for testing different Script backends
 */
class ScriptTester {

    protected $test;
    protected $rules = array();

    function ScriptTester($test)
    {
        $this->test = $test;
    }

    function addRule($rule)
    {
        $this->rules[] = $rule;
    }

    function assertDeletesMessage($fixture)
    {
        return PEAR::raiseError('Not implemented.');
    }

    function assertKeepsMessage($fixture)
    {
        return PEAR::raiseError('Not implemented.');
    }

    function assertMovesMessage($fixture, $to_folder)
    {
        return PEAR::raiseError('Not implemented.');
    }

    static function factory($type, $test)
    {
        $class = 'ScriptTester_' . $type;
        $ob = new $class($test);
        return $ob;
    }

    function _setupStorage()
    {
        $GLOBALS['ingo_storage'] = new Ingo_Storage_Memory();
        foreach ($this->rules as $ob) {
            $GLOBALS['ingo_storage']->updateRule($ob);
        }
    }

}

/**
 * Implementation of ScriptTester:: for testing 'imap' scripts
 */
class ScriptTester_imap extends ScriptTester {

    var $imap;
    var $api;

    function _setup()
    {
        $this->_setupStorage();
        $this->api = Ingo_Script_Imap_Api::factory('mock', array());
        $this->api->loadFixtures(__DIR__ . '/fixtures/');

        $GLOBALS['notification'] = new Ingo_Test_Notification;

        $this->imap = new Ingo_Script_Imap(array(
            'api' => $this->api,
            'spam_compare' => 'string',
            'spam_header' => 'X-Spam-Level',
            'spam_char' => '*',
            'filter_seen' => 0,
            'show_filter_msg' => 1
        ));
    }

    function _run()
    {
        $this->imap->perform(0);
    }

    function assertDeletesMessage($fixture)
    {
        $this->_setup();
        $this->test->assertTrue($this->api->hasMessage($fixture));
        $this->_run();
        $this->test->assertFalse($this->api->hasMessage($fixture));
    }

    function assertKeepsMessage($fixture)
    {
        $this->_setup();
        $this->test->assertTrue($this->api->hasMessage($fixture));
        $this->_run();
        $this->test->assertTrue($this->api->hasMessage($fixture));
    }

    function assertMovesMessage($fixture, $to_folder)
    {
        $this->_setup();
        $this->test->assertTrue($this->api->hasMessage($fixture));
        $this->_run();
        $this->test->assertFalse($this->api->hasMessage($fixture));
        $this->test->assertTrue($this->api->hasMessage($fixture, $to_folder));
    }

}

/**
 * This script tester iterates through all enabled backends to verify that
 * each one works properly.
 */
class ScriptTester_all extends ScriptTester {

    // No imap tests for now, until the mock searching works again.
    var $backends = array('sieve');

    function _delegate($method, $params)
    {
        foreach ($this->backends as $backend) {
            $runner = ScriptTester::factory($backend, $this->test);
            foreach ($this->rules as $rule) {
                $runner->addRule($rule);
            }
            call_user_func_array(array($runner, $method), $params);
        }
    }

    function assertDeletesMessage($fixture)
    {
        $this->_delegate('assertDeletesMessage', array($fixture));
    }

    function assertKeepsMessage($fixture)
    {
        $this->_delegate('assertKeepsMessage', array($fixture));
    }

    function assertMovesMessage($fixture, $to_folder)
    {
        $this->_delegate('assertMovesMessage', array($fixture, $to_folder));
    }

}

/**
 * Test the sieve Script backend.  This uses the command-line `sieve' from
 * the GNU mailutils package.
 */
class ScriptTester_sieve extends ScriptTester {

    function assertDeletesMessage($fixture)
    {
        $this->_run();
        $this->_assertOutput("DISCARD on msg uid " . $this->uids[$fixture]);
    }

    function assertKeepsMessage($fixture)
    {
        $this->_run();
        $this->_assertOutput("KEEP on msg uid " . $this->uids[$fixture]);
    }

    function assertMovesMessage($fixture, $to_folder)
    {
        $this->_run();
        $this->_assertOutput("FILEINTO on msg uid " . $this->uids[$fixture] .
                             ": delivering into " . $to_folder);
    }

    function _assertOutput($want)
    {
        $this->test->assertRegExp('/' . preg_quote($want, '/') . '/',
                                  $this->output,
                                  "FAILED SIEVE SCRIPT:\n\n", $this->sieve_text, "\n\n");
    }

    var $mbox;
    var $sieve;
    var $script_text;
    var $output;
    var $uids;

    function _run()
    {
        $this->_buildMailboxFile();
        $this->_writeSieveScript();
        $this->_runSieve();

        @unlink($this->mbox);
        @unlink($this->sieve);
    }

    function _buildMailboxFile()
    {
        $this->uids = array();
        $this->mbox = tempnam('/tmp', 'mbox');
        $mh = fopen($this->mbox, 'w');
        $uid = 1;

        foreach (glob(__DIR__ . '/fixtures/*') as $file) {
            $data = file_get_contents($file);
            fwrite($mh, $data);
            if (substr($data, 0, -1) != "\n") {
                fwrite($mh, "\n");
            }
            $this->uids[$file] = $uid++;
        }

        fclose($mh);
    }

    function _writeSieveScript()
    {
        $this->_setupStorage();
        $script = new Ingo_Script_Sieve(array(
            'date_format' => '%x',
            'time_format' => '%R',
            'spam_compare' => 'string',
            'spam_header' => 'X-Spam-Level',
            'spam_char' => '*'
        ));

        $this->sieve = tempnam('/tmp', 'sieve');
        $fh = fopen($this->sieve, 'w');

        $this->sieve_text = $script->generate();
        fwrite($fh, $this->sieve_text);
        fclose($fh);
    }

    function _runSieve()
    {
        $this->output = '';
        $ph = popen("sieve -vv -n -f " . escapeshellarg($this->mbox) . " " .
                    escapeshellarg($this->sieve) . ' 2>&1', 'r');
        while (!feof($ph)) {
            $data = fread($ph, 512);
            if (is_string($data)) {
                $this->output .= $data;
            }
        }
        pclose($ph);
    }

}

