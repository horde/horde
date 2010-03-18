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

class Horde_Argv_ExpandDefaultsTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_Parser(array('prog' => 'test'));
        $this->help_prefix = 'Usage: test [options]

Options:
  -h, --help            show this help message and exit';

        $this->file_help = "read from FILE [default: %default]";
        $this->expected_help_file = $this->help_prefix . "\n" .
            "  -f FILE, --file=FILE  read from FILE [default: foo.txt]\n";
        $this->expected_help_none = $this->help_prefix . "\n" .
            "  -f FILE, --file=FILE  read from FILE [default: none]\n";
    }

    public function testOptionDefault()
    {
        $this->parser->addOption("-f", "--file", array('default' => 'foo.txt', 'help' => $this->file_help));
        $this->assertHelp($this->parser, $this->expected_help_file);
    }

    public function testParserDefault1()
    {
        $this->parser->addOption("-f", "--file",
                                 array('help' => $this->file_help));
        $this->parser->setDefault('file', "foo.txt");
        $this->assertHelp($this->parser, $this->expected_help_file);
    }

    public function testParserDefault2()
    {
        $this->parser->addOption("-f", "--file",
                                 array('help' => $this->file_help));
        $this->parser->setDefaults(array('file' => 'foo.txt'));
        $this->assertHelp($this->parser, $this->expected_help_file);
    }

    public function testNoDefault()
    {
        $this->parser->addOption("-f", "--file",
                                 array('help' => $this->file_help));
        $this->assertHelp($this->parser, $this->expected_help_none);
    }

    public function testDefaultNone1()
    {
        $this->parser->addOption("-f", "--file",
                                 array('default' => null,
                                       'help' => $this->file_help));
        $this->assertHelp($this->parser, $this->expected_help_none);
    }

    public function testDefaultNone2()
    {
        $this->parser->addOption("-f", "--file",
                                 array('help' => $this->file_help));
        $this->parser->setDefaults(array('file' => null));
        $this->assertHelp($this->parser, $this->expected_help_none);
    }

    public function testFloatDefault()
    {
        $this->parser->addOption(
            "-p", "--prob",
            array('help' => "blow up with probability PROB [default: %default]"));
        $this->parser->setDefaults(array('prob' => 0.43));
        $expected_help = $this->help_prefix . "\n" .
            "  -p PROB, --prob=PROB  blow up with probability PROB [default: 0.43]\n";
        $this->assertHelp($this->parser, $expected_help);
    }

    public function testAltExpand()
    {
        $this->parser->addOption("-f", "--file",
                                 array('default' => "foo.txt",
                                       'help' => "read from FILE [default: *DEFAULT*]"));
        $this->parser->formatter->default_tag = "*DEFAULT*";
        $this->assertHelp($this->parser, $this->expected_help_file);
    }

    public function testNoExpand()
    {
        $this->parser->addOption("-f", "--file",
                                 array('default' => "foo.txt",
                                       'help' => "read from %default file"));
        $this->parser->formatter->default_tag = null;
        $expected_help = $this->help_prefix . "\n" .
            "  -f FILE, --file=FILE  read from %default file\n";
        $this->assertHelp($this->parser, $expected_help);
    }

}
