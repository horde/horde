<?php

require_once __DIR__ . '/TestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Argv
 * @subpackage UnitTests
 */

class Horde_Argv_VersionTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        if (!isset($_SERVER['argv'])) {
            $_SERVER['argv'] = array('test');
        }
    }

    public function tearDown()
    {
        unset($_SERVER['argv']);
    }

    public function testVersion()
    {
        $this->parser = new Horde_Argv_InterceptingParser(array(
            'usage'   => Horde_Argv_Option::SUPPRESS_USAGE,
            'version' => "%prog 0.1"));
        $saveArgv = $_SERVER['argv'];
        try {
            $_SERVER['argv'][0] = __DIR__ . '/foo/bar';
            $this->assertOutput(array("--version"), "bar 0.1\n");
        } catch (Exception $e) {
            $_SERVER['argv'] = $saveArgv;
            throw $e;
        }

        $_SERVER['argv'] = $saveArgv;
    }

    public function testNoVersion()
    {
        $this->parser = new Horde_Argv_InterceptingParser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
        $this->assertParseFail(array("--version"), "no such option: --version");
    }
}
