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

class Horde_Argv_CallbackExtraArgsTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $options = array(
            $this->makeOption('-p', '--point', array('action' => 'callback',
                'callback' => array($this, 'processTuple'),
                'callbackArgs' => array(3, 'int'), 'type' => 'string',
                'dest' => 'points', 'default' => array())),
        );
        $this->parser = new Horde_Argv_Parser(array('optionList' => $options));
    }

    public function processTuple($option, $opt, $value, $parser, $args)
    {
        list($len, $type) = $args;

        $this->assertEquals(3, $len);
        $this->assertEquals('int', $type);

        if ($opt == '-p') {
            $this->assertEquals('1,2,3', $value);
        } else if ($option == '--point') {
            $this->assertEquals('4,5,6', $value);
        }

        $values = explode(',', $value);
        foreach ($values as &$value) {
            settype($value, $type);
        }

        $parser->values->{$option->dest}[] = $values;
    }

    public function testCallbackExtraArgs()
    {
        $this->assertParseOk(array("-p1,2,3", "--point", "4,5,6"),
                             array('points' => array(array(1,2,3), array(4,5,6))),
                             array());
    }

}
