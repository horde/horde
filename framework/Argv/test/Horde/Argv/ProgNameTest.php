<?php

require_once dirname(__FILE__) . '/TestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

class Horde_Argv_ProgNameTest extends Horde_Argv_TestCase
{
    public function assertUsage($parser, $expectedUsage)
    {
        $this->assertEquals($parser->getUsage(), $expectedUsage);
    }

    public function assertVersion($parser, $expectedVersion)
    {
        $this->assertEquals($parser->getVersion(), $expectedVersion);
    }

    public function testDefaultProgName()
    {
        // Make sure that program name is taken from $_SERVER['argv'][0] by default.
        $saveArgv = $_SERVER['argv'];
        try {
            $_SERVER['argv'][0] = 'foo/bar/baz.php';
            $parser = new Horde_Argv_Parser(array('usage' => "%prog ...", 'version' => "%prog 1.2"));
            $expectedUsage = "Usage: baz.php ...\n";
        } catch (Exception $e) {
            $_SERVER['argv'] = $saveArgv;
            throw($e);
        }

        $this->assertUsage($parser, $expectedUsage);
        $this->assertVersion($parser, "baz.php 1.2");
        $this->assertHelp($parser,
                          $expectedUsage . "\n" .
                          "Options:\n" .
                          "  --version   show program's version number and exit\n" .
                          "  -h, --help  show this help message and exit\n");
    }

    public function testCustomProgName()
    {
        $parser = new Horde_Argv_Parser(array('prog' => 'thingy',
                                              'version' => "%prog 0.1",
                                              'usage' => "%prog arg arg"));
        $parser->removeOption('-h');
        $parser->removeOption('--version');
        $expectedUsage = "Usage: thingy arg arg\n";
        $this->assertUsage($parser, $expectedUsage);
        $this->assertVersion($parser, "thingy 0.1");
        $this->assertHelp($parser, $expectedUsage . "\n");
    }

}
