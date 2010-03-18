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

class Horde_Argv_MultipleArgsAppendTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_InterceptingParser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
        $this->parser->addOption("-p", "--point", array(
            'action' => "store", 'nargs' => 3, 'type' => 'float', 'dest' => 'point'));
        $this->parser->addOption("-f", "--foo", array(
            'action' => "append", 'nargs' => 2, 'type' => "int", 'dest' => "foo"));
        $this->parser->addOption("-z", "--zero", array(
            'action' => "append_const", 'dest' => "foo", 'const' => array(0, 0)));
    }

    public function testNargsAppend()
    {
        $this->assertParseOK(array("-f", "4", "-3", "blah", "--foo", "1", "666"),
                             array('point' => null, 'foo' => array(array(4, -3), array(1, 666))),
                             array('blah'));
    }

    public function testNargsAppendRequiredValues()
    {
        $this->assertParseFail(array("-f4,3"),
                               "-f option requires 2 arguments");
    }

    public function testNargsAppendSimple()
    {
        $this->assertParseOK(array("--foo=3", "4"),
                             array('point' => null, 'foo' => array(array(3, 4))),
                             array());
    }

    public function testNargsAppendConst()
    {
        $this->assertParseOK(array("--zero", "--foo", "3", "4", "-z"),
                             array('point' => null, 'foo' => array(array(0, 0), array(3, 4), array(0, 0))),
                             array());
    }

}
