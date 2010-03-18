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

class Horde_Argv_StandardTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $options = array(
            $this->makeOption('-a', array('type' => 'string')),
            $this->makeOption('-b', '--boo', array('type' => 'int', 'dest' => 'boo')),
            $this->makeOption('--foo', array('action' => 'append'))
        );

        $this->parser = new Horde_Argv_InterceptingParser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE,
                                                                'optionList' => $options));
    }

    public function testRequiredValue()
    {
        $this->assertParseFail(array('-a'),
                               '-a option requires an argument');
    }

    public function testInvalidInteger()
    {
        $this->assertParseFail(array('-b', '5x'),
                               "option -b: invalid integer value: '5x'");
    }

    public function testNoSuchOption()
    {
        $this->assertParseFail(array('--boo13'),
                               "no such option: --boo13");
    }

    public function testLongInvalidInteger()
    {
        $this->assertParseFail(array("--boo=x5"),
                               "option --boo: invalid integer value: 'x5'");
    }

    public function testEmpty()
    {
        $this->assertParseOk(array(),
                             array('a' => null, 'boo' => null, 'foo' => null), array());
    }

    public function testShortOptEmptyLongOptAppend()
    {
        $this->assertParseOk(array("-a", "", "--foo=blah", "--foo="),
                             array('a' => "", 'boo' => null, 'foo' => array("blah", "")),
                             array());
    }

    public function testLongOptionAppend()
    {
        $this->assertParseOk(array("--foo", "bar", "--foo", "", "--foo=x"),
                             array('a' => null,
                                   'boo' => null,
                                   'foo' => array('bar', '', 'x')),
                             array());
    }

    public function testOptionArgumentJoined()
    {
        $this->assertParseOk(array("-abc"),
                             array('a' => "bc", 'boo' => null, 'foo' => null),
                             array());
    }

    public function testOptionArgumentSplit()
    {
        $this->assertParseOk(array("-a", "34"),
                             array('a' => "34", 'boo' => null, 'foo' => null),
                             array());
    }

    public function testOptionArgumentJoinedInteger()
    {
        $this->assertParseOk(array("-b34"),
                             array('a' => null, 'boo' => 34, 'foo' => null),
                             array());
    }

    public function testOptionArgumentSplitNegativeInteger()
    {
        $this->assertParseOk(array("-b", "-5"),
                             array('a' => null, 'boo' => -5, 'foo' => null),
                             array());
    }

    public function testLongOptionArgumentJoined()
    {
        $this->assertParseOk(array("--boo=13"),
                             array('a' => null, 'boo' => 13, 'foo' => null),
                             array());
    }

    public function testLongOptionArgumentSplit()
    {
        $this->assertParseOk(array("--boo", "111"),
                             array('a' => null, 'boo' => 111, 'foo' => null),
                             array());
    }

    public function testLongOptionShortOption()
    {
        $this->assertParseOk(array("--foo=bar", "-axyz"),
                             array('a' => 'xyz', 'boo' => null, 'foo' => array("bar")),
                             array());
    }

    public function testAbbrevLongOption()
    {
        $this->assertParseOk(array("--f=bar", "-axyz"),
                             array('a' => 'xyz', 'boo' => null, 'foo' => array("bar")),
                             array());
    }

    public function testDefaults()
    {
        list($options, $args) = $this->parser->parseArgs(array());
        $defaults = $this->parser->getDefaultValues();

        $this->assertEquals($defaults, $options);
    }

    public function testAmbiguousOption()
    {
        $this->parser->addOption("--foz", array('action' => 'store',
                                                'type' => 'string', 'dest' => 'foo'));
        $this->assertParseFail(array('--f=bar'),
                               "ambiguous option: --f (--foo, --foz?)");
    }

    public function testShortAndLongOptionSplit()
    {
        $this->assertParseOk(array("-a", "xyz", "--foo", "bar"),
                             array('a' => 'xyz', 'boo' => null, 'foo' => array("bar")),
                             array());
    }

    public function testShortOptionSplitLongOptionAppend()
    {
        $this->assertParseOk(array("--foo=bar", "-b", "123", "--foo", "baz"),
                             array('a' => null, 'boo' => 123, 'foo' => array("bar", "baz")),
                             array());
    }

    public function testShortOptionSplitOnePositionalArg()
    {
        $this->assertParseOk(array("-a", "foo", "bar"),
                             array('a' => "foo", 'boo' => null, 'foo' => null),
                             array("bar"));
    }

    public function testShortOptionConsumesSeparator()
    {
        $this->assertParseOk(array("-a", "--", "foo", "bar"),
                             array('a' => "--", 'boo' => null, 'foo' => null),
                             array("foo", "bar"));

        $this->assertParseOk(array("-a", "--", "--foo", "bar"),
                             array('a' => "--", 'boo' => null, 'foo' => array("bar")),
                             array());
    }

    public function testShortOptionJoinedAndSeparator()
    {
        $this->assertParseOk(array("-ab", "--", "--foo", "bar"),
                             array('a' => "b", 'boo' => null, 'foo' => null),
                             array("--foo", "bar"));
    }

    public function testHyphenBecomesPositionalArg()
    {
        $this->assertParseOk(array("-ab", "-", "--foo", "bar"),
                             array('a' => "b", 'boo' => null, 'foo' => array("bar")),
                             array("-"));
    }

    public function testNoAppendVersusAppend()
    {
        $this->assertParseOk(array("-b3", "-b", "5", "--foo=bar", "--foo", "baz"),
                             array('a' => null, 'boo' => 5, 'foo' => array("bar", "baz")),
                             array());
    }

    public function testOptionConsumesOptionLikeString()
    {
        $this->assertParseOk(array("-a", "-b3"),
                             array('a' => "-b3", 'boo' => null, 'foo' => null),
                             array());
    }
}

