<?php

require_once dirname(__FILE__) . '/ConflictTestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

class Horde_Argv_ConflictResolveTest extends Horde_Argv_ConflictTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->parser->setConflictHandler('resolve');
        $this->parser->addOption('-v', '--version', array('action' => 'callback',
                                                          'callback' => array($this, 'showVersion'),
                                                          'help' => 'show version'));
    }

    public function testConflictResolve()
    {
        $vOpt = $this->parser->getOption('-v');
        $verboseOpt = $this->parser->getOption('--verbose');
        $versionOpt = $this->parser->getOption('--version');

        $this->assertSame($vOpt, $versionOpt);
        $this->assertNotSame($vOpt, $verboseOpt);

        $this->assertEquals(array('--version'), $vOpt->longOpts);
        $this->assertEquals(array('-v'), $versionOpt->shortOpts);
        $this->assertEquals(array('--version'), $versionOpt->longOpts);
        $this->assertEquals(array(), $verboseOpt->shortOpts);
        $this->assertEquals(array('--verbose'), $verboseOpt->longOpts);
    }

    public function testConflictResolveHelp()
    {
        $output = "Options:\n"
                . "  --verbose      increment verbosity\n"
                . "  -h, --help     show this help message and exit\n"
                . "  -v, --version  show version\n";

        $this->assertOutput(array('-h'), $output);
    }

    public function testConflictResolveShortOpt()
    {
        $this->assertParseOk(array('-v'),
                             array('verbose' => null, 'showVersion' => 1),
                             array());
    }

    public function testConflictResolveLongOpt()
    {
        $this->assertParseOk(array('--verbose'),
                             array('verbose' => 1),
                             array());
    }

    public function testConflictResolveLongOpts()
    {
        $this->assertParseOk(array('--verbose', '--version'),
                             array('verbose' => 1, 'showVersion' => 1),
                             array());
    }
}
