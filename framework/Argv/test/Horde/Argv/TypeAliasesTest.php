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

class Horde_Argv_TypeAliasesTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_Parser();
    }

    public function testStrAliasesString()
    {
        $this->parser->addOption("-s", array('type' => "str"));
        $this->assertEquals($this->parser->getOption("-s")->type, "string");
    }

    public function testNewTypeObject()
    {
        $this->parser->addOption("-s", array('type' => 'str'));
        $this->assertEquals($this->parser->getOption("-s")->type, "string");
        $this->parser->addOption("-x", array('type' => 'int'));
        $this->assertEquals($this->parser->getOption("-x")->type, "int");
    }

}
