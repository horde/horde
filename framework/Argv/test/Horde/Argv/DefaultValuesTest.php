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

class Horde_Argv_DefaultValuesTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_Parser();
        $this->parser->addOption('-v', '--verbose', array('default' => true));
        $this->parser->addOption('-q', '--quiet', array('dest' => 'verbose'));
        $this->parser->addOption('-n', array('type' => 'int', 'default' => 37));
        $this->parser->addOption('-m', array('type' => 'int'));
        $this->parser->addOption('-s', array('default' => 'foo'));
        $this->parser->addOption('-t');
        $this->parser->addOption('-u', array('default' => null));

        $this->expected = array('verbose' => true,
                                'n' => 37,
                                'm' => null,
                                's' => 'foo',
                                't' => null,
                                'u' => null);
    }

    public function testBasicDefault()
    {
        $this->assertEquals($this->expected, iterator_to_array($this->parser->getDefaultValues()));
    }

    public function testMixedDefaultsPost()
    {
        $this->parser->setDefaults(array('n' => 42, 'm' => -100));
        $this->expected = array_merge($this->expected, array('n' => 42, 'm' => -100));
        $this->assertEquals($this->expected, iterator_to_array($this->parser->getDefaultValues()));
    }

    public function testMixedDefaultsPre()
    {
        $this->parser->setDefaults(array('x' => 'barf', 'y' => 'blah'));
        $this->parser->addOption('-x', array('default' => 'frob'));
        $this->parser->addOption('-y');

        $this->expected = array_merge($this->expected, array('x' => 'frob', 'y' => 'blah'));
        $this->assertEquals($this->expected, iterator_to_array($this->parser->getDefaultValues()));

        $this->parser->removeOption('-y');
        $this->parser->addOption('-y', array('default' => null));
        $this->expected = array_merge($this->expected, array('y' => null));
        $this->assertEquals($this->expected, iterator_to_array($this->parser->getDefaultValues()));
    }

    public function testProcessDefault()
    {
        $this->parser->optionClass = 'Horde_Argv_DurationOption';
        $this->parser->addOption('-d', array('type' => 'duration', 'default' => 300));
        $this->parser->addOption('-e', array('type' => 'duration', 'default' => '6m'));
        $this->parser->setDefaults(array('n' => '42'));

        $this->expected = array_merge($this->expected, array('d' => 300, 'e' => 360, 'n' => '42'));
        $this->assertEquals($this->expected, iterator_to_array($this->parser->getDefaultValues()));
    }
}

class Horde_Argv_DurationOption extends Horde_Argv_Option
{
    public $TYPES = array('string', 'int', 'long', 'float', 'complex', 'choice', 'duration');

    public $TYPE_CHECKER = array('int'    => 'checkBuiltin',
                                 'long'   => 'checkBuiltin',
                                 'float'  => 'checkBuiltin',
                                 'complex'=> 'checkBuiltin',
                                 'choice' => 'checkChoice',
                                 'duration' => 'checkDuration',
    );

    public function checkDuration($opt, $value)
    {
        // Custom type for testing processing of default values.
        $time_units = array('s' => 1, 'm' => 60, 'h' => 60 * 60, 'd' => 60 * 60 * 24);

        $last = substr($value, -1);
        if (is_numeric($last)) {
            return (int)$value;
        } elseif (isset($time_units[$last])) {
            return (int)substr($value, 0, -1) * $time_units[$last];
        } else {
            throw new Horde_Argv_OptionValueException(sprintf(
                'option %s: invalid duration: %s', $opt, $value));
        }
    }

}
