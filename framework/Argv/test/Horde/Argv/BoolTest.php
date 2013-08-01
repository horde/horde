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

class Horde_Argv_BoolTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $options = array(
            $this->makeOption('-v', '--verbose',
                        array('action' => 'store_true', 'dest' => 'verbose', 'default' => '')),
            $this->makeOption('-q', '--quiet',
                        array('action' => 'store_false', 'dest' => 'verbose'))
        );

        $this->parser = new Horde_Argv_Parser(array('optionList' => $options));
    }

    public function testBoolDefault()
    {
        $this->assertParseOk(array(),
                             array('verbose' => ''),
                             array());
    }

    public function testBoolFalse()
    {
        list($options, $args) = $this->assertParseOk(array('-q'),
                                                     array('verbose' => false),
                                                     array());

        $this->assertSame(false, $options->verbose);
    }

    public function testBoolTrue()
    {
        list($options, $args) = $this->assertParseOk(array('-v'),
                                                     array('verbose' => true),
                                                     array());
        $this->assertSame(true, $options->verbose);
    }

    public function testBoolFlickerOnAndOff()
    {
        $this->assertParseOk(array('-qvq', '-q', '-v'),
                             array('verbose' => true),
                             array());
    }

}
