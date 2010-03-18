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

class Horde_Argv_CallbackManyArgsTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $options = array(
            $this->makeOption('-a', '--apple', array('action' => 'callback', 'nargs' => 2,
                                                        'callback' => array($this, 'processMany'), 'type' => 'string')),
            $this->makeOption('-b', '--bob', array('action' => 'callback', 'nargs' => 3,
                                                       'callback' => array($this, 'processMany'), 'type' => 'int'))
        );

        $this->parser = new Horde_Argv_Parser(array('optionList' => $options));
    }

    public function processMany($option, $opt, $value, $parser_)
    {
        if ($opt == '-a') {
            $this->assertEquals(array('foo', 'bar'), $value);
        } else if ($opt == '--apple') {
            $this->assertEquals(array('ding', 'dong'), $value);
        } else if ($opt == '-b') {
            $this->assertEquals(array(1, 2, 3), $value);
        } else if ($option == '--bob') {
            $this->assertEquals(array(-666, 42, 0), $value);
        }
    }

    public function testManyArgs()
    {
        $this->assertParseOk(array("-a", "foo", "bar", "--apple", "ding", "dong",
                             "-b", "1", "2", "3", "--bob", "-666", "42",
                             "0"),
                             array('apple' => null, 'bob' => null),
                             array());
    }
}
