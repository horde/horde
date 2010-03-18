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

class Horde_Argv_ParseNumTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_InterceptingParser();
        $this->parser->addOption('-n', array('type' => 'int'));
        $this->parser->addOption('-l', array('type' => 'long'));
    }

    public function testParseNumFail()
    {
        $this->assertFalse(Horde_Argv_Option::parseNumber(''));
        $this->assertFalse(Horde_Argv_Option::parseNumber("0xOoops"));
    }

    public function testParseNumOk()
    {
        $this->assertSame(0,
                          Horde_Argv_Option::parseNumber('0'));
        $this->assertSame(16,
                          Horde_Argv_Option::parseNumber('0x10'));
        $this->assertSame(10,
                          Horde_Argv_Option::parseNumber('0XA'));
        $this->assertSame(8,
                          Horde_Argv_Option::parseNumber('010'));
        $this->assertSame(3,
                          Horde_Argv_Option::parseNumber('0b11'));
        $this->assertSame(0,
                          Horde_Argv_Option::parseNumber('0b'));
    }

    public function testNumericOptions()
    {
        $this->assertParseOk(array("-n", "42", "-l", "0x20"),
                             array("n" => 42, "l" => 0x20), array());

        $this->assertParseOk(array("-n", "0b0101", "-l010"),
                             array("n" => 5, "l" => 8), array());

        $this->assertParseFail(array("-n008"),
                               "option -n: invalid integer value: '008'");

        $this->assertParseFail(array("-l0b0123"),
                               "option -l: invalid long integer value: '0b0123'");

        $this->assertParseFail(array("-l", "0x12x"),
                               "option -l: invalid long integer value: '0x12x'");
    }
}
