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

class Horde_Argv_HelpTest extends Horde_Argv_TestCase
{

    public static $expected_help_basic = 'Usage: bar.php [options]

Options:
  -a APPLE           throw APPLEs at basket
  -b NUM, --boo=NUM  shout "boo!" NUM times (in order to frighten away all the
                     evil spirits that cause trouble and mayhem)
  --foo=FOO          store FOO in the foo list for later fooing
  -h, --help         show this help message and exit
';

    public static $expected_help_long_opts_first = 'Usage: bar.php [options]

Options:
  -a APPLE           throw APPLEs at basket
  --boo=NUM, -b NUM  shout "boo!" NUM times (in order to frighten away all the
                     evil spirits that cause trouble and mayhem)
  --foo=FOO          store FOO in the foo list for later fooing
  --help, -h         show this help message and exit
';

    public static $expected_help_title_formatter = 'Usage
=====
  bar.php [options]

Options
=======
-a APPLE           throw APPLEs at basket
--boo=NUM, -b NUM  shout "boo!" NUM times (in order to frighten away all the
                   evil spirits that cause trouble and mayhem)
--foo=FOO          store FOO in the foo list for later fooing
--help, -h         show this help message and exit
';

    public static $expected_help_short_lines = 'Usage: bar.php [options]

Options:
  -a APPLE           throw APPLEs at basket
  -b NUM, --boo=NUM  shout "boo!" NUM times (in order to
                     frighten away all the evil spirits
                     that cause trouble and mayhem)
  --foo=FOO          store FOO in the foo list for later
                     fooing
  -h, --help         show this help message and exit
';

    public function setUp()
    {
        $this->parser = $this->makeParser(80);
        $this->origColumns = isset($_ENV['COLUMNS']) ? $_ENV['COLUMNS'] : null;
    }

    public function tearDown()
    {
        if (is_null($this->origColumns)) {
            unset($_ENV['COLUMNS']);
        } else {
            $_ENV['COLUMNS'] = $this->origColumns;
        }
    }

    public function makeParser($columns)
    {
        $options = array(
            $this->makeOption("-a", array('type' => "string", 'dest' => 'a',
                                          'metavar' => "APPLE", 'help' => "throw APPLEs at basket")),

            $this->makeOption("-b", "--boo", array('type'    => "int", 'dest' => 'boo',
                                                   'metavar' => "NUM",
                                                   'help'    => "shout \"boo!\" NUM times (in order to frighten away " .
                                                                "all the evil spirits that cause trouble and mayhem)")),

            $this->makeOption("--foo", array('action' => 'append', 'type' => 'string', 'dest' => 'foo',
                                             'help' => "store FOO in the foo list for later fooing")),
        );

        $_ENV['COLUMNS'] = $columns;

        return new Horde_Argv_InterceptingParser(array('optionList' => $options));
    }

    public function assertHelpEquals($expectedOutput)
    {
        // @todo
        // if type(expected_output) is types.UnicodeType:
        //     encoding = self.parser._get_encoding(sys.stdout)
        //     expected_output = expected_output.encode(encoding, "replace")

        $origArgv = $_SERVER['argv'];
        $_SERVER['argv'][0] = 'foo/bar.php';
        $this->assertOutput(array('-h'), $expectedOutput);

        $_SERVER['argv'] = $origArgv;
    }

    public function testHelp()
    {
        $this->assertHelpEquals(self::$expected_help_basic);
    }

    public function tesHelpOldUsage()
    {
        $this->parser->setUsage("Usage: %prog [options]");
        $this->assertHelpEquals(self::$expected_help_basic);
    }

    public function testHelpLongOptsFirst()
    {
        $this->parser->formatter->short_first = false;
        $this->assertHelpEquals(self::$expected_help_long_opts_first);
    }

    public function testHelpTitleFormatter()
    {
        $this->parser->formatter = new Horde_Argv_TitledHelpFormatter();
        $this->assertHelpEquals(self::$expected_help_title_formatter);
    }

    public function testWrapColumns()
    {
        // Ensure that wrapping respects $COLUMNS environment variable.
        // Need to reconstruct the parser, since that's the only time
        // we look at $COLUMNS.
        $this->parser = $this->makeParser(60);
        $this->assertHelpEquals(self::$expected_help_short_lines);
    }

    public function testHelpDescriptionGroups()
    {
        $this->parser->setDescription(
            "This is the program description for %prog.  %prog has " .
            "an option group as well as single options.");

        $group = new Horde_Argv_OptionGroup(
            $this->parser, "Dangerous Options",
            "Caution: use of these options is at your own risk.  " .
            "It is believed that some of them bite.");
        $group->addOption("-g", array('action' => "store_true", 'help' => "Group option."));
        $this->parser->addOptionGroup($group);

        $expect = 'Usage: bar.php [options]

This is the program description for bar.php.  bar.php has an option group as
well as single options.

Options:
  -a APPLE           throw APPLEs at basket
  -b NUM, --boo=NUM  shout "boo!" NUM times (in order to frighten away all the
                     evil spirits that cause trouble and mayhem)
  --foo=FOO          store FOO in the foo list for later fooing
  -h, --help         show this help message and exit

  Dangerous Options:
    Caution: use of these options is at your own risk.  It is believed
    that some of them bite.

    -g               Group option.
';

        $this->assertHelpEquals($expect);

        $this->parser->epilog = "Please report bugs to /dev/null.";
        $this->assertHelpEquals($expect . "\nPlease report bugs to /dev/null.\n");
    }

    /* @todo
    def test_help_unicode(self):
        self.parser = Horde_Argv_InterceptingParser(usage=Horde_Argv_Option::SUPPRESS_USAGE)
        self.parser.addOption("-a", action="store_true", help=u"ol\u00E9!")
        expect = u"""\
Options:
  -h, --help  show this help message and exit
  -a          ol\u00E9!
"""
        self.assertHelpEquals(expect)

    def test_help_unicode_description(self):
        self.parser = Horde_Argv_InterceptingParser(usage=Horde_Argv_Option::SUPPRESS_USAGE,
                                                    description=u"ol\u00E9!")
        expect = u"""\
ol\u00E9!

Options:
  -h, --help  show this help message and exit
"""
        self.assertHelpEquals(expect)

    */

}
