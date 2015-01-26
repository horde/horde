<?php
/**
 * Copyright 2006 Anish Mistry
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @package   ManageSieve
 * @author    Anish Mistry <amistry@am-productions.biz>
 * @author    Jan Schneider <jan@horde.org>
 * @license   http://www.horde.org/licenses/bsd BSD
 */

use \Horde, \Horde\ManageSieve, \Horde\ManageSieve\Exception;

/**
 * PHPUnit test case for Horde\ManageSieve.
 *
 * @package   ManageSieve
 * @author    Anish Mistry <amistry@am-productions.biz>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2006 Anish Mistry
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 */
class ManageSieveTest extends Horde_Test_Case
{
    /**
     * The ManageSieve client.
     *
     * @var \Horde\ManageSieve
     */
    protected $fixture;

    /**
     * Server configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * The tested scripts.
     *
     * @var array
     */
    protected $scripts;

    protected function setUp()
    {
        $this->config = self::getConfig('MANAGESIEVE_TEST_CONFIG');
        if (!$this->config || empty($this->config['managesieve'])) {
            $this->markTestSkipped('No ManageSieve configuration');
            return;
        }
        $this->config = $this->config['managesieve'];

        // Create a new instance of Horde\ManageSieve.
        $this->fixture = new ManageSieve();
        $this->scripts = array(
            'test script1' => "require \"fileinto\";\r\nif header :contains \"From\" \"@cnba.uba.ar\" \r\n{fileinto \"INBOX.Test1\";}\r\nelse \r\n{fileinto \"INBOX\";}",
            'test script2' => "require \"fileinto\";\r\nif header :contains \"From\" \"@cnba.uba.ar\" \r\n{fileinto \"INBOX.Test\";}\r\nelse \r\n{fileinto \"INBOX\";}",
            'test"scriptäöü3' => "require \"vacation\";\nvacation\n:days 7\n:addresses [\"matthew@de-construct.com\"]\n:subject \"This is a test\"\n\"I'm on my holiday!\nsadfafs\";",
            'test script4' => file_get_contents(dirname(__FILE__) . '/largescript.siv'));
    }
    
    protected function tearDown()
    {
        // Delete the instance.
        unset($this->fixture);
    }
    
    protected function login()
    {
        $this->fixture->connect($this->config['host'], $this->config['port']);
        $this->fixture->login($this->config['username'], $this->config['password'], null, '', false);
    }

    protected function logout()
    {
        $this->fixture->disconnect();
    }

    protected function clear()
    {
        // Clear all the scripts in the account.
        $this->login();
        $active = $this->fixture->getActive();
        if (isset($this->scripts[$active])) {
            $this->fixture->setActive(null);
        }
        foreach (array_keys($this->scripts) as $script) {
            try {
                $this->fixture->removeScript($script);
            } catch (Exception $e) {
            }
        }
        $this->logout();
    }

    public function testConnect()
    {
        $this->fixture->connect($this->config['host'], $this->config['port']);
    }
    
    public function testLogin()
    {
        $this->fixture->connect($this->config['host'], $this->config['port']);
        $this->fixture->login($this->config['username'], $this->config['password'], null, '', false);
    }

    public function testDisconnect()
    {
        $this->fixture->connect($this->config['host'], $this->config['port']);
        $this->fixture->login($this->config['username'], $this->config['password'], null, '', false);
        $this->fixture->disconnect();
    }

    public function testListScripts()
    {
        $this->login();
        $this->fixture->listScripts();
        $this->logout();
    }

    public function testInstallScript()
    {
        $this->clear();
        $this->login();

        // First script.
        $scriptname = 'test script1';
        $before_scripts = $this->fixture->listScripts();
        $this->fixture->installScript($scriptname, $this->scripts[$scriptname]);
        $after_scripts = $this->fixture->listScripts();
        $diff_scripts = array_values(array_diff($after_scripts, $before_scripts));
        $this->assertTrue(count($diff_scripts) > 0, 'Script not installed');
        $this->assertEquals($scriptname, $diff_scripts[0], 'Added script has a different name');

        // Second script (install and activate)
        $scriptname = 'test script2';
        $before_scripts = $this->fixture->listScripts();
        $this->fixture->installScript($scriptname, $this->scripts[$scriptname], true);
        $after_scripts = $this->fixture->listScripts();
        $diff_scripts = array_values(array_diff($after_scripts, $before_scripts));
        $this->assertTrue(count($diff_scripts) > 0, 'Script not installed');
        $this->assertEquals($scriptname, $diff_scripts[0], 'Added script has a different name');
        $active_script = $this->fixture->getActive();
        $this->assertEquals($scriptname, $active_script, 'Added script has a different name');
        $this->logout();
    }

    /**
     * There is a good chance that this test will fail since most servers have
     * a 32KB limit on uploaded scripts.
     *
     * @expectedException \Horde\ManageSieve\Exception
     */
    public function testInstallScriptLarge()
    {
        $this->clear();
        $this->login();
        $scriptname = 'test script4';
        $before_scripts = $this->fixture->listScripts();
        $this->fixture->installScript($scriptname, $this->scripts[$scriptname]);
        $after_scripts = $this->fixture->listScripts();
        $diff_scripts = array_diff($after_scripts, $before_scripts);
        $this->assertEquals($scriptname, reset($diff_scripts), 'Added script has a different name');
        $this->logout();
    }

    /**
     * See bug #16691.
     */
    public function testInstallNonAsciiScript()
    {
        $this->clear();
        $this->login();

        $scriptname = 'test"scriptäöü3';
        $before_scripts = $this->fixture->listScripts();
        $this->fixture->installScript($scriptname, $this->scripts[$scriptname]);
        $after_scripts = $this->fixture->listScripts();
        $diff_scripts = array_values(array_diff($after_scripts, $before_scripts));
        $this->assertTrue(count($diff_scripts) > 0, 'Script not installed');
        $this->assertEquals($scriptname, $diff_scripts[0], 'Added script has a different name');

        $this->logout();
    }

    public function testGetScript()
    {
        $this->clear();
        $this->login();
        $scriptname = 'test script1';
        $before_scripts = $this->fixture->listScripts();
        $this->fixture->installScript($scriptname, $this->scripts[$scriptname]);
        $after_scripts = $this->fixture->listScripts();
        $diff_scripts = array_values(array_diff($after_scripts, $before_scripts));
        $this->assertTrue(count($diff_scripts) > 0);
        $this->assertEquals($scriptname, $diff_scripts[0], 'Added script has a different name');
        $script = $this->fixture->getScript($scriptname);
        $this->assertEquals(trim($this->scripts[$scriptname]), trim($script), 'Script installed it not the same script retrieved');
        $this->logout();
    }

    public function testGetActive()
    {
        $this->clear();
        $this->login();
        $active_script = $this->fixture->getActive();
        $this->logout();
    }

    public function testSetActive()
    {
        $this->clear();
        $scriptname = 'test script1';
        $this->login();
        $result = $this->fixture->installScript($scriptname, $this->scripts[$scriptname]);
        $this->fixture->setActive($scriptname);
        $active_script = $this->fixture->getActive();
        $this->assertEquals($scriptname, $active_script, 'Active script does not match');

        // Test for non-existant script.
        try {
            $this->fixture->setActive('non existant script');
            $this->fail('Exception expected');
        } catch (Exception $e) {
        }
        $this->logout();
    }

    public function testRemoveScript()
    {
        $this->clear();
        $scriptname = 'test script1';
        $this->login();
        $this->fixture->installScript($scriptname, $this->scripts[$scriptname]);
        $this->fixture->removeScript($scriptname);
        $this->logout();
    }
}
