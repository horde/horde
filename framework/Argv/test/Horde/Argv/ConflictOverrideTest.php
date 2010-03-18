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

class Horde_Argv_ConflictOverrideTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_InterceptingParser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
        $this->parser->setConflictHandler('resolve');
        $this->parser->addOption('-n', '--dry-run',
                                 array('action' => 'store_true', 'dest' => 'dry_run',
                                       'help' => "don't do anything"));
        $this->parser->addOption('--dry-run', '-n',
                                 array('action' => 'store_const', 'const' => 42, 'dest' => 'dry_run',
                                       'help' => 'dry run mode'));
    }

    public function testConflictOverrideOpts()
    {
        $opt = $this->parser->getOption('--dry-run');

        $this->assertEquals(array('-n'), $opt->shortOpts);
        $this->assertEquals(array('--dry-run'), $opt->longOpts);
    }

    public function testConflictOverrideHelp()
    {
        $output = "Options:\n"
                . "  -h, --help     show this help message and exit\n"
                . "  -n, --dry-run  dry run mode\n";
        $this->assertOutput(array('-h'), $output);
    }

    public function testConflictOverrideArgs()
    {
        $this->assertParseOk(array('-n'),
                             array('dry_run' => 42),
                             array());
    }
}
