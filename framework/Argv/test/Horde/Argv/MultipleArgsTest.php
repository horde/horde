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

class Horde_Argv_MultipleArgsTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_InterceptingParser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
        $this->parser->addOption("-p", "--point",
                                 array('action' => "store", 'nargs' => 3, 'type' => "float", 'dest' => "point"));
    }

    public function testNargsWithPositionalArgs()
    {
        $this->assertParseOK(array("foo", "-p", "1", "2.5", "-4.3", "xyz"),
                             array('point' => array(1.0, 2.5, -4.3)),
                             array('foo', 'xyz'));
    }

    public function testNargsLongOpt()
    {
        $this->assertParseOK(array("--point", "-1", "2.5", "-0", "xyz"),
                             array('point' => array(-1.0, 2.5, -0.0)),
                             array("xyz"));
    }

    public function testNargsInvalidFloatValue()
    {
        $this->assertParseFail(array("-p", "1.0", "2x", "3.5"),
                               "option -p: invalid floating-point value: '2x'");
    }

    public function testNargsRequiredValues()
    {
        $this->assertParseFail(array("--point", "1.0", "3.5"),
                               "--point option requires 3 arguments");
    }

}
