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

class Horde_Argv_ExtendAddActionsTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $options = array(new Horde_Argv_ExtendAddActionsTest_MyOption("-a", "--apple", array(
            'action' => "extend", 'type' => "string", 'dest' => "apple")));
        $this->parser = new Horde_Argv_Parser(array('optionList' => $options));
    }

    public function testExtendAddAction()
    {
        $this->assertParseOK(array("-afoo,bar", "--apple=blah"),
                             array('apple' => array("foo", "bar", "blah")),
                             array());
    }

    public function testExtendAddActionNormal()
    {
        $this->assertParseOK(array("-a", "foo", "-abar", "--apple=x,y"),
                             array('apple' => array("foo", "bar", "x", "y")),
                             array());
    }

}

class Horde_Argv_ExtendAddActionsTest_MyOption extends Horde_Argv_Option
{
    public $ACTIONS = array("store",
                            "store_const",
                            "store_true",
                            "store_false",
                            "append",
                            "append_const",
                            "count",
                            "callback",
                            "help",
                            "version",
                            "extend",
                            );

    public $STORE_ACTIONS = array("store",
                     "store_const",
                     "store_true",
                     "store_false",
                     "append",
                     "append_const",
                     "count",
                     "extend",
                                  );

    public $TYPED_ACTIONS = array("store",
                                  "append",
                                  "callback",
                                  "extend",
                                  );

    public function takeAction($action, $dest, $opt, $value, $values, $parser)
    {
        if ($action == "extend") {
            $lvalue = explode(',', $value);
            $values->$dest = array_merge($values->ensureValue($dest, array()), $lvalue);
        } else {
            parent::takeAction($action, $dest, $opt, $parser, $value, $values);
        }
    }

}
