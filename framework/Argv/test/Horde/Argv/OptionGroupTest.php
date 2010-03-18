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

class Horde_Argv_OptionGroupTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_Parser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
    }

    public function testOptionGroupCreateInstance()
    {
        $group = new Horde_Argv_OptionGroup($this->parser, "Spam");
        $this->parser->addOptionGroup($group);
        $group->addOption("--spam", array('action' => "store_true",
                                          'help' => "spam spam spam spam"));
        $this->assertParseOK(array("--spam"), array('spam' => true), array());
    }

    public function testAddGroupNoGroup()
    {
        $this->assertTypeError(array($this->parser, 'addOptionGroup'),
                               "not an OptionGroup instance: NULL", array(null));
    }

    public function testAddGroupInvalidArguments()
    {
        $this->assertTypeError(array($this->parser, 'addOptionGroup'),
                               "invalid arguments", null);
    }

    public function testAddGroupWrongParser()
    {
        $group = new Horde_Argv_OptionGroup($this->parser, "Spam");
        $group->parser = new Horde_Argv_Parser();
        $this->assertRaises(array($this->parser, 'addOptionGroup'), array($group),
                            'InvalidArgumentException', "invalid OptionGroup (wrong parser)");
    }

    public function testGroupManipulate()
    {
        $group = $this->parser->addOptionGroup("Group 2",
                                               array('description' => "Some more options"));
        $group->setTitle("Bacon");
        $group->addOption("--bacon", array('type' => "int"));
        $this->assertSame($group, $this->parser->getOptionGroup("--bacon"));
    }

}
