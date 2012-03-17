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

class Horde_Argv_ChoiceTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->parser = new Horde_Argv_InterceptingParser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
        $this->parser->addOption('-c', array('action' => 'store', 'type' => 'choice',
                                 'dest' => 'choice', 'choices' => array('one', 'two', 'three')));
    }

    public function testValidChoice()
    {
        $this->assertParseOk(array('-c', 'one', 'xyz'),
                             array('choice' => 'one'),
                             array('xyz'));
    }

    public function testInvalidChoice()
    {
        $this->assertParseFail(array('-c', 'four', 'abc'),
                               "option -c: invalid choice: 'four' " .
                               "(choose from 'one', 'two', 'three')");
    }

    public function testAddChoiceOption()
    {
        $this->parser->addOption('-d', '--default', array('choices' => array('four', 'five', 'six')));
        $opt = $this->parser->getOption('-d');
        $this->assertEquals('choice', $opt->type);
        $this->assertEquals('store', $opt->action);
    }
}
