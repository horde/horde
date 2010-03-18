<?php

require_once dirname(__FILE__) . '/TestCase.php';
require_once dirname(__FILE__) . '/InterceptingParser.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

class Horde_Argv_CallbackVarArgsTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $options = array(
            $this->makeOption('-a', array('type' => 'int', 'nargs' => 2, 'dest' => 'a')),
            $this->makeOption('-b', array('action' => 'store_true', 'dest' => 'b')),
            $this->makeOption('-c', '--callback', array('action' => 'callback', 'callback' => array($this, 'variableArgs'), 'dest' => 'c')),
        );
        $this->parser = new Horde_Argv_InterceptingParser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE,
                                                                'optionList' => $options));
    }

    public function variableArgs($option, $opt, $value, $parser)
    {
        $this->assertNull($value);
        $done = 0;
        $value = array();
        $rargs =& $parser->rargs;
        while ($rargs) {
            $arg = $rargs[0];
            if ((substr($arg, 0, 2) == '--' && strlen($arg) > 2) ||
                (substr($arg, 0, 1) == '-' && strlen($arg) > 1 && substr($arg, 1, 1) != '-')) {
                break;
            } else {
                $value[] = $arg;
                array_shift($rargs);
            }
        }
        $parser->values->{$option->dest} = $value;
    }

    public function testVariableArgs()
    {
        $this->assertParseOK(array('-a3', '-5', '--callback', 'foo', 'bar'),
                             array('a' => array(3, -5), 'b' => null, 'c' => array('foo', 'bar')),
                             array());
    }

    public function testConsumeSeparatorStopAtOption()
    {
        $this->assertParseOK(array('-c', '37', '--', 'xxx', '-b', 'hello'),
                             array('a' => null, 'b' => true, 'c' => array('37', '--', 'xxx')),
                             array('hello'));
    }

    public function testPositionalArgAndVariableArgs()
    {
        $this->assertParseOK(array('hello', '-c', 'foo', '-', 'bar'),
                             array('a' => null, 'b' => null, 'c' => array('foo', '-', 'bar')),
                             array('hello'));
    }

    public function testStopAtOption()
    {
        $this->assertParseOK(array('-c', 'foo', '-b'),
                             array('a' => null, 'b' => true, 'c' => array('foo')),
                             array());
    }

    public function testStopAtInvalidOption()
    {
        $this->assertParseFail(array('-c', '3', '-5', '-a'), 'no such option: -5');
    }

}
