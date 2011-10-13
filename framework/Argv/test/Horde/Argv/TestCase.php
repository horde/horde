<?php
/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Argv
 * @subpackage UnitTests
 */

class Horde_Argv_TestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        setlocale(LC_ALL, 'C');
    }

    public function makeOption()
    {
        $args = func_get_args();
        $reflector = new ReflectionClass('Horde_Argv_Option');
        return $reflector->newInstanceArgs($args);
    }

    /**
     * Assert the options are what we expected when parsing arguments.
     *
     * Otherwise, fail with a nicely formatted message.
     *
     * Keyword arguments:
     * args -- A list of arguments to parse with Horde_Argv_Parser.
     * expected_opts -- The options expected.
     * expected_positional_args -- The positional arguments expected.
     *
     * Returns the options and positional args for further testing.
     */
    public function assertParseOK($args, $expected_opts, $expected_positional_args)
    {
        list($options, $positional_args) = $this->parser->parseArgs($args);
        $optdict = iterator_to_array($options);

        $this->assertEquals($expected_opts, $optdict,
                            'Expected options don\'t match. Args were ' . print_r($args, true));

        $this->assertEquals($positional_args, $expected_positional_args,
                            'Positional arguments don\'t match. Args were ' . print_r($args, true));

        return array($options, $positional_args);
    }

    /**
     *  Assert that the expected exception is raised when calling a
     *  function, and that the right error message is included with
     *  that exception.
     *
     *  Arguments:
     *    func -- the function to call
     *    args -- positional arguments to `func`
     *    expected_exception -- exception that should be raised
     *    expected_message -- expected exception message (or pattern
     *      if a compiled regex object)
     *
     *  Returns the exception raised for further testing.
     */
    public function assertRaises($func, $args = array(),
                                 $expected_exception, $expected_message) {
        $caught = false;
        try {
            if (is_array($args)) {
                call_user_func_array($func, $args);
            } else {
                call_user_func($func);
            }
        } catch (Exception $e) {
            if (get_class($e) == $expected_exception) {
                $caught = true;
                $this->assertEquals($expected_message, $e->getMessage(), 'Expected exception message not matched');
            }
        }

        if (!$caught) {
            $this->fail("Expected exception $expected_exception not thrown");
        }
    }

    // -- Assertions used in more than one class --------------------

    /**
     *   Assert the parser fails with the expected message.  Caller
     *   must ensure that $this->parser is an InterceptingParser.
     */
    public function assertParseFail($cmdline_args, $expected_output)
    {
        try {
            $this->parser->parseArgs($cmdline_args);
        } catch (Horde_Argv_InterceptedException $e) {
            $this->assertEquals($expected_output, (string)$e);
            return true;
        } catch (Exception $e) {
            $this->fail("unexpected Exception: " . $e->getMessage());
        }

        $this->fail("expected parse failure");
    }

    /**
     * Assert the parser prints the expected output on stdout.
     */
    public function assertOutput(
        $cmdline_args,
        $expected_output,
        $expected_status = 0,
        $expected_error = null)
    {
        ob_start();
        try {
            $this->parser->parseArgs($cmdline_args);
        } catch (Horde_Argv_InterceptedException $e) {
            $output = ob_get_clean();

            $this->assertEquals($expected_output, $output, 'Expected parser output to match');
            $this->assertEquals($expected_status, $e->exit_status);
            $this->assertEquals($expected_error, $e->exit_message);
            return;
        }

        ob_end_clean();

        $this->fail("expected parser->parserExit()");
    }

    /**
     * Assert that TypeError is raised when executing func.
     */
    public function assertTypeError($func, $expected_message, $args)
    {
        $this->assertRaises($func, $args, 'InvalidArgumentException', $expected_message);
    }

    public function assertHelp($parser, $expected_help)
    {
        $actual_help = $parser->formatHelp();
        $this->assertEquals($expected_help, $actual_help);
    }

}
