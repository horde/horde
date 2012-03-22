<?php

require_once __DIR__ . '/TestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Argv
 * @subpackage UnitTests
 */

class Horde_Argv_TypeAliasesTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        parent::setUp();
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
