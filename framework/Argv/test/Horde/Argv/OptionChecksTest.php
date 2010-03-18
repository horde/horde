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

class Horde_Argv_OptionChecksTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_Parser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
    }

    public function assertOptionError($expected_message, $args)
    {
        $this->assertRaises(array($this, 'makeOption'), $args, 'Horde_Argv_OptionException', $expected_message);
    }

    public function testOptStringEmpty()
    {
        try {
            new Horde_Argv_Option();
        } catch (Exception $e) {
            $this->assertType('InvalidArgumentException', $e);
            $this->assertEquals("at least one option string must be supplied", $e->getMessage());
            return true;
        }

        $this->fail("InvalidArgumentException for no option strings not thrown");
    }

    public function testOptStringTooShort()
    {
        $this->assertOptionError(
            "invalid option string 'b': must be at least two characters long",
            array("b"));
    }

    public function testOptStringShortInvalid()
    {
        $this->assertOptionError(
            "invalid short option string '--': must be " .
            "of the form -x, (x any non-dash char)",
            array("--"));
    }

    public function testOptStringLongInvalid()
    {
        $this->assertOptionError(
            "invalid long option string '---': " .
            "must start with --, followed by non-dash",
            array("---"));
    }

    public function testAttrInvalid()
    {
        $this->assertOptionError(
            "option -b: invalid keyword arguments: bar, foo",
            array("-b", array('foo' => null, 'bar' => null)));
    }

    public function testActionInvalid()
    {
        $this->assertOptionError(
            "option -b: invalid action: 'foo'",
            array("-b", array('action' => 'foo')));
    }

    public function testTypeInvalid()
    {
        $this->assertOptionError(
            "option -b: invalid option type: 'foo'",
            array("-b", array('type' => 'foo')));
        $this->assertOptionError(
            "option -b: invalid option type: 'Array'",
            array("-b", array('type' => array())));
    }

    public function testNoTypeForAction()
    {
        $this->assertOptionError(
            "option -b: must not supply a type for action 'count'",
            array("-b", array('action' => 'count', 'type' => 'int')));
    }

    public function testNoChoicesList()
    {
        $this->assertOptionError(
            "option -b/--bad: must supply a list of " .
            "choices for type 'choice'",
            array("-b", "--bad", array('type' => "choice")));
    }

    public function testBadChoicesList()
    {
        $typename = gettype('');
        $this->assertOptionError(
            sprintf("option -b/--bad: choices must be a list of " .
                    "strings ('%s' supplied)", $typename),
            array("-b", "--bad", array('type' => 'choice', 'choices' => 'bad choices')));
    }

    public function testNoChoicesForType()
    {
        $this->assertOptionError(
            "option -b: must not supply choices for type 'int'",
            array("-b", array('type' => 'int', 'choices' => "bad")));
    }

    public function testNoConstForAction()
    {
        $this->assertOptionError(
            "option -b: 'const' must not be supplied for action 'store'",
            array("-b", array('action' => 'store', 'const' => 1)));
    }

    public function testNoNargsForAction()
    {
        $this->assertOptionError(
            "option -b: 'nargs' must not be supplied for action 'count'",
            array("-b", array('action' => 'count', 'nargs' => 2)));
    }

    public function testCallbackNotCallable()
    {
        $this->assertOptionError(
            "option -b: callback not callable: 'foo'",
            array("-b", array('action' => 'callback', 'callback' => 'foo')));
    }

    public function dummy()
    {
    }

    public function testCallbackArgsNoArray()
    {
        $this->assertOptionError(
            "option -b: callbackArgs, if supplied, " .
            "must be an array: not 'foo'",
            array("-b", array('action' => 'callback',
                              'callback' => array($this, 'dummy'),
                              'callbackArgs' => 'foo')));
    }

    public function testNoCallbackForAction()
    {
        $this->assertOptionError(
            "option -b: callback supplied ('foo') for non-callback option",
            array("-b", array('action' => 'store',
                              'callback' => 'foo')));
    }

    public function testNoCallbackArgsForAction()
    {
        $this->assertOptionError(
            "option -b: callbackArgs supplied for non-callback option",
            array("-b", array('action' => 'store',
                              'callbackArgs' => 'foo')));
    }

}
